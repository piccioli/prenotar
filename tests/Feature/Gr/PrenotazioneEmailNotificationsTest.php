<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneApprovata;
use App\Events\PrenotazioneInviata;
use App\Events\PrenotazioneRifiutata;
use App\Events\PrenotazioneTorreRiassegnata;
use App\Listeners\SendPrenotazioneApprovataNotification;
use App\Listeners\SendPrenotazioneInviataNotification;
use App\Listeners\SendPrenotazioneRifiutataNotification;
use App\Listeners\SendPrenotazioneTorreRiassegnataNotification;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use App\Notifications\PrenotazioneApprovataNotification;
use App\Notifications\PrenotazioneInviataAlGrNotification;
use App\Notifications\PrenotazioneRifiutataNotification;
use App\Notifications\PrenotazioneTorreRiassegnataNotification;
use App\Services\PrenotazioneStateMachine;
use App\Settings\GrSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre1 = Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
    $this->torre2 = Torre::factory()->create(['nome' => 'Torre 2', 'is_active' => true]);
    $this->gr = User::factory()->grManager()->create();
    $this->sezione = User::factory()->sezione()->create();
    $this->machine = app(PrenotazioneStateMachine::class);

    // Configura almeno un'email GR per i test di inviaRichiesta
    $settings = app(GrSettings::class);
    $settings->emails_notifiche_gr = ['gr-test@example.com'];
    $settings->save();
});

test('inviaRichiesta → PrenotazioneInviataAlGrNotification arriva alle email GR', function (): void {
    Notification::fake();

    $pren = Prenotazione::factory()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);
    $pren->addMedia(UploadedFile::fake()->image('delibera.jpg'))->toMediaCollection('delibera_consiglio');

    // Attiva il listener direttamente (in test il queue è sync)
    $listener = app(SendPrenotazioneInviataNotification::class);
    $listener->handle(new PrenotazioneInviata($pren));

    Notification::assertSentOnDemand(
        PrenotazioneInviataAlGrNotification::class,
        fn ($notification, $channels, $notifiable) => in_array('gr-test@example.com', (array) $notifiable->routes['mail'], strict: true)
    );
});

test('approva → PrenotazioneApprovataNotification arriva al proprietario', function (): void {
    Notification::fake();

    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    $this->machine->approva($pren, $this->gr);

    // Attiva il listener direttamente
    $listener = app(SendPrenotazioneApprovataNotification::class);
    $listener->handle(new PrenotazioneApprovata($pren->fresh()));

    Notification::assertSentTo($this->sezione, PrenotazioneApprovataNotification::class);
});

test('rifiuta → PrenotazioneRifiutataNotification arriva al proprietario con motivo', function (): void {
    Notification::fake();

    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione->id]);

    $this->machine->rifiuta($pren, $this->gr, 'Date non disponibili per manutenzione');

    $listener = app(SendPrenotazioneRifiutataNotification::class);
    $listener->handle(new PrenotazioneRifiutata($pren->fresh(), 'Date non disponibili per manutenzione'));

    Notification::assertSentTo(
        $this->sezione,
        PrenotazioneRifiutataNotification::class,
        fn ($notification) => str_contains((string) $notification->toMail($this->sezione)->render(), 'Date non disponibili per manutenzione')
    );
});

test('reassignTorre → PrenotazioneTorreRiassegnataNotification arriva al proprietario', function (): void {
    Notification::fake();

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    $this->machine->reassignTorre($pren, $this->gr, $this->torre2->id);

    $listener = app(SendPrenotazioneTorreRiassegnataNotification::class);
    $listener->handle(new PrenotazioneTorreRiassegnata($pren->fresh(), $this->torre1->id));

    Notification::assertSentTo($this->sezione, PrenotazioneTorreRiassegnataNotification::class);
});

test('listener InviataAlGr non invia se emails_notifiche_gr è vuoto', function (): void {
    Notification::fake();

    $settings = app(GrSettings::class);
    $settings->emails_notifiche_gr = [];
    $settings->save();

    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione->id]);

    $listener = app(SendPrenotazioneInviataNotification::class);
    $listener->handle(new PrenotazioneInviata($pren));

    Notification::assertNothingSent();
});
