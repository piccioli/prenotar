<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneApprovata;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Models\User;
use App\Services\PrenotazioneStateMachine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre1 = Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
    $this->torre2 = Torre::factory()->create(['nome' => 'Torre 2', 'is_active' => true]);
    $this->gr = User::factory()->grManager()->create();
    $this->sezione = User::factory()->sezione()->create();
    $this->machine = app(PrenotazioneStateMachine::class);
});

test('approva porta lo status a Approvata e scrive la history', function (): void {
    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    $this->machine->approva($pren, $this->gr);

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::Approvata)
        ->and($pren->approvato_da)->toBe($this->gr->id)
        ->and($pren->approvato_at)->not->toBeNull();

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->status_from)->toBe(PrenotazioneStatus::Inviata)
        ->and($history->status_to)->toBe(PrenotazioneStatus::Approvata)
        ->and($history->user_id)->toBe($this->gr->id);
});

test('approva fa dispatch dell\'evento PrenotazioneApprovata', function (): void {
    Event::fake([PrenotazioneApprovata::class]);

    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    $this->machine->approva($pren, $this->gr);

    Event::assertDispatched(PrenotazioneApprovata::class, fn ($e) => $e->prenotazione->id === $pren->id);
});

test('approva lancia DomainException se status non è Inviata', function (): void {
    $pren = Prenotazione::factory()->create([
        'user_id' => $this->sezione->id,
        'status' => PrenotazioneStatus::Bozza,
    ]);

    expect(fn () => $this->machine->approva($pren, $this->gr))
        ->toThrow(DomainException::class, 'stato Inviata');
});

test('approva con torreId riassegna la torre contestualmente', function (): void {
    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    $this->machine->approva($pren, $this->gr, $this->torre2->id);

    $pren->refresh();
    expect($pren->torre_id)->toBe($this->torre2->id)
        ->and($pren->status)->toBe(PrenotazioneStatus::Approvata);

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)->first();
    expect($history->note)->toContain('torre riassegnata');
});

test('approva con torreId blocca se la torre target ha overlap', function (): void {
    Prenotazione::factory()->inviata()->create([
        'torre_id' => $this->torre2->id,
        'data_inizio_prenotazione' => today()->addDays(28),
        'data_fine_prenotazione' => today()->addDays(33),
    ]);

    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    expect(fn () => $this->machine->approva($pren, $this->gr, $this->torre2->id))
        ->toThrow(ValidationException::class);
});

test('approva con stesso torreId non fa re-validazione overlap inutile', function (): void {
    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    // Torre identica: non genera errore, il metodo la ignora
    $this->machine->approva($pren, $this->gr, $this->torre1->id);

    expect($pren->fresh()->status)->toBe(PrenotazioneStatus::Approvata);
});
