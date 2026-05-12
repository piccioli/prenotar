<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Jobs\ArchiveCompletedReservations;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Models\User;
use App\Services\PrenotazioneStateMachine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre = Torre::factory()->create();
    $this->sezione = User::factory()->sezione()->create();
});

test('il job conclude prenotazioni con data_fine_evento passata', function (): void {
    $pren = Prenotazione::factory()->inviatoAssicurazione()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_fine_evento' => today()->subDay(),
    ]);

    $this->artisan('prenotazioni:nightly');
    // run the queued job synchronously
    (new ArchiveCompletedReservations)->handle(app(PrenotazioneStateMachine::class));

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::Concluso)
        ->and($pren->concluso_at)->not->toBeNull()
        ->and($pren->archived_at)->not->toBeNull();

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)
        ->where('status_to', PrenotazioneStatus::Concluso)
        ->first();
    expect($history)->not->toBeNull()
        ->and($history->note)->toContain('[SISTEMA]');
});

test('il job non tocca prenotazioni con evento futuro', function (): void {
    $pren = Prenotazione::factory()->inviatoAssicurazione()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_fine_evento' => today()->addDays(2),
    ]);

    (new ArchiveCompletedReservations)->handle(app(PrenotazioneStateMachine::class));

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::InviatoAssicurazione);
});

test('il job è idempotente: doppio run non crea doppia history', function (): void {
    $pren = Prenotazione::factory()->inviatoAssicurazione()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_fine_evento' => today()->subDay(),
    ]);

    $job = new ArchiveCompletedReservations;
    $sm = app(PrenotazioneStateMachine::class);

    $job->handle($sm);
    $countAfterFirst = PrenotazioneHistory::where('prenotazione_id', $pren->id)->count();

    $job->handle($sm);

    expect(PrenotazioneHistory::where('prenotazione_id', $pren->id)->count())->toBe($countAfterFirst);
});
