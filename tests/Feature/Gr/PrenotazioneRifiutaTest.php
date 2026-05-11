<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneRifiutata;
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
    $this->torre = Torre::factory()->create(['is_active' => true]);
    $this->gr = User::factory()->grManager()->create();
    $this->sezione = User::factory()->sezione()->create();
    $this->machine = app(PrenotazioneStateMachine::class);
});

test('rifiuta porta lo status a Annullata con motivo e archived_at', function (): void {
    $pren = Prenotazione::factory()->inviata()->create([
        'user_id' => $this->sezione->id,
        'torre_id' => $this->torre->id,
    ]);

    $this->machine->rifiuta($pren, $this->gr, 'Documenti incompleti');

    $pren->refresh();
    expect($pren->status)->toBe(PrenotazioneStatus::Annullata)
        ->and($pren->motivo_rifiuto)->toBe('Documenti incompleti')
        ->and($pren->archived_at)->not->toBeNull();
});

test('rifiuta scrive la history con il motivo', function (): void {
    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione->id]);

    $this->machine->rifiuta($pren, $this->gr, 'Date non disponibili');

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->status_from)->toBe(PrenotazioneStatus::Inviata)
        ->and($history->status_to)->toBe(PrenotazioneStatus::Annullata)
        ->and($history->user_id)->toBe($this->gr->id)
        ->and($history->note)->toContain('Date non disponibili');
});

test('rifiuta fa dispatch dell\'evento PrenotazioneRifiutata con il motivo', function (): void {
    Event::fake([PrenotazioneRifiutata::class]);

    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione->id]);

    $this->machine->rifiuta($pren, $this->gr, 'Periodo sovrapposto');

    Event::assertDispatched(
        PrenotazioneRifiutata::class,
        fn ($e) => $e->prenotazione->id === $pren->id && $e->motivo === 'Periodo sovrapposto'
    );
});

test('rifiuta lancia DomainException se status non è Inviata', function (): void {
    $pren = Prenotazione::factory()->approvata()->create(['user_id' => $this->sezione->id]);

    expect(fn () => $this->machine->rifiuta($pren, $this->gr, 'motivo'))
        ->toThrow(DomainException::class, 'stato Inviata');
});

test('rifiuta lancia InvalidArgumentException se il motivo è vuoto', function (): void {
    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione->id]);

    expect(fn () => $this->machine->rifiuta($pren, $this->gr, ''))
        ->toThrow(InvalidArgumentException::class, 'obbligatorio');
});

test('rifiuta lancia InvalidArgumentException se il motivo è solo whitespace', function (): void {
    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione->id]);

    expect(fn () => $this->machine->rifiuta($pren, $this->gr, '   '))
        ->toThrow(InvalidArgumentException::class, 'obbligatorio');
});
