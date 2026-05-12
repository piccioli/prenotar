<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneConclusa;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Models\User;
use App\Services\PrenotazioneStateMachine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre = Torre::factory()->create();
    $this->sezione = User::factory()->sezione()->create();
    $this->sm = app(PrenotazioneStateMachine::class);
});

test('concludi transisce da InviatoAssicurazione a Concluso', function (): void {
    Event::fake([PrenotazioneConclusa::class]);

    $pren = Prenotazione::factory()->inviatoAssicurazione()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_fine_evento' => today()->subDay(),
    ]);

    $this->sm->concludi($pren);

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::Concluso)
        ->and($pren->concluso_at)->not->toBeNull()
        ->and($pren->archived_at)->not->toBeNull();

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)
        ->where('status_to', PrenotazioneStatus::Concluso)
        ->first();
    expect($history)->not->toBeNull()
        ->and($history->user_id)->toBeNull()
        ->and($history->note)->toContain('[SISTEMA]');

    Event::assertDispatched(PrenotazioneConclusa::class, fn ($e) => $e->prenotazione->id === $pren->id && $e->actor === null);
});

test('concludi con actor valorizza user_id nella history', function (): void {
    Event::fake([PrenotazioneConclusa::class]);
    $gr = User::factory()->grManager()->create();

    $pren = Prenotazione::factory()->inviatoAssicurazione()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    $this->sm->concludi($pren, $gr);

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)
        ->where('status_to', PrenotazioneStatus::Concluso)
        ->first();
    expect($history->user_id)->toBe($gr->id)
        ->and($history->note)->not->toContain('[SISTEMA]');
});

test('concludi lancia DomainException se stato non è InviatoAssicurazione', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    expect(fn () => $this->sm->concludi($pren))
        ->toThrow(DomainException::class);
});

test('concludi è idempotente: doppio run non duplica la history', function (): void {
    $pren = Prenotazione::factory()->inviatoAssicurazione()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    $this->sm->concludi($pren);

    $count = PrenotazioneHistory::where('prenotazione_id', $pren->id)->count();

    expect(fn () => $this->sm->concludi($pren->fresh()))
        ->toThrow(DomainException::class);

    expect(PrenotazioneHistory::where('prenotazione_id', $pren->id)->count())->toBe($count);
});
