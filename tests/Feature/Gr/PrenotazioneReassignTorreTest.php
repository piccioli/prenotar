<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneTorreRiassegnata;
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

test('reassignTorre cambia la torre su una prenotazione Approvata', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    $this->machine->reassignTorre($pren, $this->gr, $this->torre2->id);

    expect($pren->fresh()->torre_id)->toBe($this->torre2->id);
});

test('reassignTorre scrive la history con le torri vecchia e nuova', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    $this->machine->reassignTorre($pren, $this->gr, $this->torre2->id);

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->status_from)->toBe(PrenotazioneStatus::Approvata)
        ->and($history->status_to)->toBe(PrenotazioneStatus::Approvata)
        ->and($history->note)->toContain((string) $this->torre1->id)
        ->and($history->note)->toContain((string) $this->torre2->id);
});

test('reassignTorre fa dispatch dell\'evento PrenotazioneTorreRiassegnata', function (): void {
    Event::fake([PrenotazioneTorreRiassegnata::class]);

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    $this->machine->reassignTorre($pren, $this->gr, $this->torre2->id);

    Event::assertDispatched(
        PrenotazioneTorreRiassegnata::class,
        fn ($e) => $e->prenotazione->id === $pren->id && $e->torreVecchiaId === $this->torre1->id
    );
});

test('reassignTorre funziona anche su stato InviatoPdfFirmato', function (): void {
    $pren = Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    $this->machine->reassignTorre($pren, $this->gr, $this->torre2->id);

    expect($pren->fresh()->torre_id)->toBe($this->torre2->id);
});

test('reassignTorre lancia DomainException su stato Inviata', function (): void {
    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
    ]);

    expect(fn () => $this->machine->reassignTorre($pren, $this->gr, $this->torre2->id))
        ->toThrow(DomainException::class);
});

test('reassignTorre lancia DomainException su stato Annullata', function (): void {
    $pren = Prenotazione::factory()->annullata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
    ]);

    expect(fn () => $this->machine->reassignTorre($pren, $this->gr, $this->torre2->id))
        ->toThrow(DomainException::class);
});

test('reassignTorre lancia DomainException se la torre è già quella assegnata', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    expect(fn () => $this->machine->reassignTorre($pren, $this->gr, $this->torre1->id))
        ->toThrow(DomainException::class, 'già assegnata');
});

test('reassignTorre blocca se la torre target ha overlap con altra prenotazione attiva', function (): void {
    Prenotazione::factory()->inviata()->create([
        'torre_id' => $this->torre2->id,
        'data_inizio_prenotazione' => today()->addDays(28),
        'data_fine_prenotazione' => today()->addDays(33),
    ]);

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    expect(fn () => $this->machine->reassignTorre($pren, $this->gr, $this->torre2->id))
        ->toThrow(ValidationException::class);
});
