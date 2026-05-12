<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneInviataAssicurazione;
use App\Mail\Modulo3Mail;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Models\User;
use App\Services\PrenotazioneStateMachine;
use App\Settings\GrSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->torre = Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
    $this->gr = User::factory()->grManager()->create();
    $this->sezione = User::factory()->sezione()->create();
    $this->machine = app(PrenotazioneStateMachine::class);

    $settings = app(GrSettings::class);
    $settings->emails_assicurazione = ['assicurazione@example.com'];
    $settings->emails_notifiche_gr = ['gr@example.com'];
    $settings->save();
});

test('inviaAssicurazione su InviatoPdfFirmato aggiorna status e history', function (): void {
    $pren = Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    $this->machine->inviaAssicurazione($pren, $this->gr);

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::InviatoAssicurazione)
        ->and($pren->inviato_assicurazione_at)->not->toBeNull();

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)
        ->orderBy('id', 'desc')
        ->first();
    expect($history->user_id)->toBe($this->gr->id)
        ->and($history->status_from)->toBe(PrenotazioneStatus::InviatoPdfFirmato)
        ->and($history->status_to)->toBe(PrenotazioneStatus::InviatoAssicurazione)
        ->and($history->note)->toContain('assicurativa');
});

test('inviaAssicurazione dispatcha evento PrenotazioneInviataAssicurazione', function (): void {
    Event::fake([PrenotazioneInviataAssicurazione::class]);

    $pren = Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    $this->machine->inviaAssicurazione($pren, $this->gr);

    Event::assertDispatched(PrenotazioneInviataAssicurazione::class, fn ($e) => $e->prenotazione->id === $pren->id);
});

test('inviaAssicurazione lancia DomainException se status non è InviatoPdfFirmato', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    expect(fn () => $this->machine->inviaAssicurazione($pren, $this->gr))
        ->toThrow(DomainException::class, 'INVIATO_PDF_FIRMATO');
});

test('listener invia Modulo3Mail ai destinatari assicurazione con CC alla sezione', function (): void {
    Mail::fake();

    $pren = Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'nome_evento' => 'Arrampicata Test',
        'data_inizio_prenotazione' => today()->addDays(30)->toDateString(),
        'data_fine_prenotazione' => today()->addDays(35)->toDateString(),
    ]);

    $this->machine->inviaAssicurazione($pren, $this->gr);

    Mail::assertQueued(Modulo3Mail::class, fn (Modulo3Mail $mail) => $mail->hasTo('assicurazione@example.com')
        && $mail->hasCc($this->sezione->email));
});
