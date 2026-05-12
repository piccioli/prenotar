<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneDateModificate;
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
    $this->torre = Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
    $this->gr = User::factory()->grManager()->create();
    $this->sezione = User::factory()->sezione()->create();
    $this->machine = app(PrenotazioneStateMachine::class);

    $this->dataInizio = today()->addDays(30)->toDateString();
    $this->dataFine = today()->addDays(35)->toDateString();
});

test('changeDates aggiorna data_ritiro e data_riconsegna su prenotazione Inviata', function (): void {
    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_inizio_prenotazione' => $this->dataInizio,
        'data_fine_prenotazione' => $this->dataFine,
        'data_ritiro' => today()->addDays(29)->toDateString(),
        'data_riconsegna' => today()->addDays(36)->toDateString(),
    ]);

    $nuovoRitiro = today()->addDays(28)->toDateString();
    $nuovaRiconsegna = today()->addDays(37)->toDateString();

    $this->machine->changeDates($pren, $this->gr, $nuovoRitiro, $nuovaRiconsegna, 'Cambio per accordo con trasportatore');

    $pren->refresh();
    expect($pren->data_ritiro->toDateString())->toBe($nuovoRitiro)
        ->and($pren->data_riconsegna->toDateString())->toBe($nuovaRiconsegna)
        ->and($pren->status)->toBe(PrenotazioneStatus::Inviata);
});

test('changeDates aggiorna le date su prenotazione Approvata', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_inizio_prenotazione' => $this->dataInizio,
        'data_fine_prenotazione' => $this->dataFine,
        'data_ritiro' => today()->addDays(29)->toDateString(),
        'data_riconsegna' => today()->addDays(36)->toDateString(),
    ]);

    $this->machine->changeDates($pren, $this->gr, today()->addDays(28)->toDateString(), today()->addDays(37)->toDateString(), 'Motivo test');

    expect($pren->fresh()->data_ritiro->toDateString())->toBe(today()->addDays(28)->toDateString());
});

test('changeDates aggiorna le date su prenotazione InviatoPdfFirmato', function (): void {
    $pren = Prenotazione::factory()->inviatoPdfFirmato()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_inizio_prenotazione' => $this->dataInizio,
        'data_fine_prenotazione' => $this->dataFine,
        'data_ritiro' => today()->addDays(29)->toDateString(),
        'data_riconsegna' => today()->addDays(36)->toDateString(),
    ]);

    $this->machine->changeDates($pren, $this->gr, today()->addDays(27)->toDateString(), null, 'Solo ritiro modificato');

    expect($pren->fresh()->data_ritiro->toDateString())->toBe(today()->addDays(27)->toDateString())
        ->and($pren->fresh()->data_riconsegna)->toBeNull();
});

test('changeDates registra un record history con motivo', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_ritiro' => today()->addDays(29)->toDateString(),
        'data_riconsegna' => today()->addDays(36)->toDateString(),
    ]);

    $this->machine->changeDates($pren, $this->gr, today()->addDays(28)->toDateString(), today()->addDays(37)->toDateString(), 'Accordo con il trasportatore');

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->user_id)->toBe($this->gr->id)
        ->and($history->status_from)->toBe(PrenotazioneStatus::Approvata)
        ->and($history->status_to)->toBe(PrenotazioneStatus::Approvata)
        ->and($history->note)->toContain('Accordo con il trasportatore');
});

test('changeDates fa dispatch dell\'evento PrenotazioneDateModificate', function (): void {
    Event::fake([PrenotazioneDateModificate::class]);

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
        'data_ritiro' => today()->addDays(29)->toDateString(),
        'data_riconsegna' => today()->addDays(36)->toDateString(),
    ]);

    $this->machine->changeDates($pren, $this->gr, today()->addDays(28)->toDateString(), today()->addDays(37)->toDateString(), 'Test evento');

    Event::assertDispatched(PrenotazioneDateModificate::class, fn ($e) => $e->prenotazione->id === $pren->id
        && $e->motivo === 'Test evento');
});

test('changeDates lancia DomainException se status è Bozza', function (): void {
    $pren = Prenotazione::factory()->create([
        'user_id' => $this->sezione->id,
        'status' => PrenotazioneStatus::Bozza,
    ]);

    expect(fn () => $this->machine->changeDates($pren, $this->gr, today()->addDays(5)->toDateString(), null, 'motivo'))
        ->toThrow(DomainException::class, 'prima dell\'invio');
});

test('changeDates lancia DomainException se status è Annullata', function (): void {
    $pren = Prenotazione::factory()->annullata()->create([
        'user_id' => $this->sezione->id,
    ]);

    expect(fn () => $this->machine->changeDates($pren, $this->gr, today()->addDays(5)->toDateString(), null, 'motivo'))
        ->toThrow(DomainException::class);
});

test('changeDates lancia DomainException se status è InviatoAssicurazione', function (): void {
    $pren = Prenotazione::factory()->inviatoAssicurazione()->create([
        'user_id' => $this->sezione->id,
    ]);

    expect(fn () => $this->machine->changeDates($pren, $this->gr, today()->addDays(5)->toDateString(), null, 'motivo'))
        ->toThrow(DomainException::class);
});

test('changeDates lancia InvalidArgumentException se motivo è vuoto', function (): void {
    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'data_ritiro' => today()->addDays(29)->toDateString(),
    ]);

    expect(fn () => $this->machine->changeDates($pren, $this->gr, today()->addDays(28)->toDateString(), null, '   '))
        ->toThrow(InvalidArgumentException::class, 'obbligatorio');
});

test('changeDates lancia DomainException se nessuna data cambia effettivamente', function (): void {
    $ritiro = today()->addDays(29)->toDateString();
    $riconsegna = today()->addDays(36)->toDateString();

    $pren = Prenotazione::factory()->approvata()->create([
        'user_id' => $this->sezione->id,
        'data_ritiro' => $ritiro,
        'data_riconsegna' => $riconsegna,
    ]);

    expect(fn () => $this->machine->changeDates($pren, $this->gr, $ritiro, $riconsegna, 'motivo'))
        ->toThrow(DomainException::class, 'modificata');
});
