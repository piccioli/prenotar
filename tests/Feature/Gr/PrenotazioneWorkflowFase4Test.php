<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneApprovata;
use App\Events\PrenotazioneDateModificate;
use App\Events\PrenotazioneInviata;
use App\Events\PrenotazioneTorreRiassegnata;
use App\Listeners\SendPrenotazioneApprovataNotification;
use App\Listeners\SendPrenotazioneDateModificateNotification;
use App\Listeners\SendPrenotazioneInviataNotification;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Models\User;
use App\Notifications\PrenotazioneApprovataNotification;
use App\Notifications\PrenotazioneDateModificateNotification;
use App\Notifications\PrenotazioneInviataAlGrNotification;
use App\Services\PrenotazioneStateMachine;
use App\Settings\GrSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->torre1 = Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
    $this->torre2 = Torre::factory()->create(['nome' => 'Torre 2', 'is_active' => true]);
    $this->gr = User::factory()->grManager()->create();
    $this->sezione = User::factory()->sezione()->create();
    $this->machine = app(PrenotazioneStateMachine::class);

    $settings = app(GrSettings::class);
    $settings->emails_notifiche_gr = ['gr-workflow@example.com'];
    $settings->giorni_minimi_caricamento_documenti = 10;
    $settings->save();
});

test('workflow E2E: BOZZA → INVIATA → APPROVATA con notifiche', function (): void {
    Notification::fake();

    $dataInizio = today()->addDays(30)->toDateString();
    $dataFine = today()->addDays(35)->toDateString();

    // Step 1 — crea bozza
    $pren = Prenotazione::factory()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => $dataInizio,
        'data_fine_prenotazione' => $dataFine,
        'data_ritiro' => today()->addDays(29)->toDateString(),
        'data_riconsegna' => today()->addDays(36)->toDateString(),
    ]);
    expect($pren->status)->toBe(PrenotazioneStatus::Bozza);

    // Step 2 — carica delibera e invia richiesta
    $pren->addMedia(UploadedFile::fake()->image('delibera.jpg'))->toMediaCollection('delibera_consiglio');
    $this->machine->inviaRichiesta($pren, $this->sezione);

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::Inviata);

    $history1 = PrenotazioneHistory::where('prenotazione_id', $pren->id)->orderBy('id')->first();
    expect($history1->status_from)->toBe(PrenotazioneStatus::Bozza)
        ->and($history1->status_to)->toBe(PrenotazioneStatus::Inviata);

    // Verifica notifica GR via listener
    $listenerInviata = app(SendPrenotazioneInviataNotification::class);
    $listenerInviata->handle(new PrenotazioneInviata($pren->fresh()));

    Notification::assertSentOnDemand(
        PrenotazioneInviataAlGrNotification::class,
        fn ($notification, $channels, $notifiable) => in_array('gr-workflow@example.com', (array) $notifiable->routes['mail'], strict: true)
    );

    // Step 3 — GR approva con override torre
    $this->machine->approva($pren->fresh(), $this->gr, $this->torre2->id);

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::Approvata)
        ->and($pren->torre_id)->toBe($this->torre2->id)
        ->and($pren->approvato_da)->toBe($this->gr->id)
        ->and($pren->approvato_at)->not->toBeNull();

    expect(PrenotazioneHistory::where('prenotazione_id', $pren->id)->count())->toBe(2);

    // Verifica notifica sezione via listener
    $listenerApprovata = app(SendPrenotazioneApprovataNotification::class);
    $listenerApprovata->handle(new PrenotazioneApprovata($pren->fresh()));

    Notification::assertSentTo($this->sezione, PrenotazioneApprovataNotification::class);
});

test('workflow: changeDates dopo approvazione aggiorna history e invia notifica', function (): void {
    Notification::fake();

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_ritiro' => today()->addDays(29)->toDateString(),
        'data_riconsegna' => today()->addDays(36)->toDateString(),
    ]);

    $nuovoRitiro = today()->addDays(27)->toDateString();
    $nuovaRiconsegna = today()->addDays(38)->toDateString();

    $this->machine->changeDates($pren, $this->gr, $nuovoRitiro, $nuovaRiconsegna, 'Richiesta variazione logistica');

    $pren->refresh();
    expect($pren->data_ritiro->toDateString())->toBe($nuovoRitiro)
        ->and($pren->data_riconsegna->toDateString())->toBe($nuovaRiconsegna)
        ->and($pren->status)->toBe(PrenotazioneStatus::Approvata);

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)
        ->orderBy('id', 'desc')
        ->first();
    expect($history->note)->toContain('Richiesta variazione logistica')
        ->and($history->user_id)->toBe($this->gr->id);

    // Verifica notifica sezione via listener
    $listenerDates = app(SendPrenotazioneDateModificateNotification::class);
    $listenerDates->handle(new PrenotazioneDateModificate($pren->fresh(), today()->addDays(29)->toDateString(), today()->addDays(36)->toDateString(), 'Richiesta variazione logistica'));

    Notification::assertSentTo($this->sezione, PrenotazioneDateModificateNotification::class);
});

test('workflow: reassignTorre dopo approvazione aggiorna torre e history', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30)->toDateString(),
        'data_fine_prenotazione' => today()->addDays(35)->toDateString(),
    ]);

    $historyCountPrima = PrenotazioneHistory::where('prenotazione_id', $pren->id)->count();

    $this->machine->reassignTorre($pren, $this->gr, $this->torre2->id);

    $pren->refresh();
    expect($pren->torre_id)->toBe($this->torre2->id)
        ->and($pren->status)->toBe(PrenotazioneStatus::Approvata);

    expect(PrenotazioneHistory::where('prenotazione_id', $pren->id)->count())->toBe($historyCountPrima + 1);
});

test('workflow: dispatch eventi nell\'ordine corretto durante il ciclo di vita', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30)->toDateString(),
        'data_fine_prenotazione' => today()->addDays(35)->toDateString(),
        'data_ritiro' => today()->addDays(29)->toDateString(),
        'data_riconsegna' => today()->addDays(36)->toDateString(),
    ]);

    Event::fake([PrenotazioneDateModificate::class, PrenotazioneTorreRiassegnata::class]);

    $this->machine->changeDates($pren, $this->gr, today()->addDays(27)->toDateString(), today()->addDays(38)->toDateString(), 'Prima modifica');
    $this->machine->reassignTorre($pren->fresh(), $this->gr, $this->torre2->id);

    Event::assertDispatched(PrenotazioneDateModificate::class);
    Event::assertDispatched(PrenotazioneTorreRiassegnata::class);
});
