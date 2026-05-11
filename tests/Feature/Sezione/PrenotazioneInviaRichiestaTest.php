<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Events\PrenotazioneInviata;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\Torre;
use App\Models\User;
use App\Services\PrenotazioneStateMachine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre = Torre::factory()->create(['is_active' => true]);
    $this->user = User::factory()->sezione()->create();
    $this->machine = app(PrenotazioneStateMachine::class);
});

test('inviaRichiesta lancia DomainException se la prenotazione non è bozza', function (): void {
    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $this->user->id]);

    expect(fn () => $this->machine->inviaRichiesta($pren, $this->user))
        ->toThrow(DomainException::class, 'Solo le prenotazioni in bozza');
});

test('inviaRichiesta lancia DomainException se manca la delibera', function (): void {
    $pren = Prenotazione::factory()->create([
        'user_id' => $this->user->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);

    expect(fn () => $this->machine->inviaRichiesta($pren, $this->user))
        ->toThrow(DomainException::class, 'delibera del consiglio');
});

test('inviaRichiesta lancia DomainException se la data è troppo vicina', function (): void {
    $pren = Prenotazione::factory()->create([
        'user_id' => $this->user->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => today()->addDays(5), // meno di 10 giorni default
        'data_fine_prenotazione' => today()->addDays(7),
    ]);
    $pren->addMedia(
        UploadedFile::fake()->image('delibera.jpg', 10, 10)
    )->toMediaCollection('delibera_consiglio');

    expect(fn () => $this->machine->inviaRichiesta($pren, $this->user))
        ->toThrow(DomainException::class, 'almeno');
});

test('inviaRichiesta porta lo status a Inviata e scrive la history', function (): void {
    $pren = Prenotazione::factory()->create([
        'user_id' => $this->user->id,
        'torre_id' => $this->torre->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);
    $pren->addMedia(
        UploadedFile::fake()->image('delibera.jpg', 10, 10)
    )->toMediaCollection('delibera_consiglio');

    $this->machine->inviaRichiesta($pren, $this->user);

    expect($pren->fresh()->status)->toBe(PrenotazioneStatus::Inviata);

    $history = PrenotazioneHistory::where('prenotazione_id', $pren->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->status_from)->toBe(PrenotazioneStatus::Bozza)
        ->and($history->status_to)->toBe(PrenotazioneStatus::Inviata)
        ->and($history->user_id)->toBe($this->user->id);
});

test('inviaRichiesta fa dispatch dell\'evento PrenotazioneInviata', function (): void {
    Event::fake([PrenotazioneInviata::class]);

    $pren = Prenotazione::factory()->create([
        'user_id' => $this->user->id,
        'torre_id' => $this->torre->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);
    $pren->addMedia(
        UploadedFile::fake()->image('delibera.jpg', 10, 10)
    )->toMediaCollection('delibera_consiglio');

    $this->machine->inviaRichiesta($pren, $this->user);

    Event::assertDispatched(PrenotazioneInviata::class, fn ($e) => $e->prenotazione->id === $pren->id);
});

test('inviaRichiesta blocca se esiste overlap su torre', function (): void {
    Prenotazione::factory()->inviata()->create([
        'torre_id' => $this->torre->id,
        'data_inizio_prenotazione' => today()->addDays(29),
        'data_fine_prenotazione' => today()->addDays(34),
    ]);

    $pren = Prenotazione::factory()->create([
        'user_id' => $this->user->id,
        'torre_id' => $this->torre->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);
    $pren->addMedia(
        UploadedFile::fake()->image('delibera.jpg', 10, 10)
    )->toMediaCollection('delibera_consiglio');

    expect(fn () => $this->machine->inviaRichiesta($pren, $this->user))
        ->toThrow(ValidationException::class);
});

test('inviaRichiesta blocca se l\'utente ha già una prenotazione attiva', function (): void {
    Prenotazione::factory()->inviata()->create(['user_id' => $this->user->id]);

    $pren = Prenotazione::factory()->create([
        'user_id' => $this->user->id,
        'torre_id' => null,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => today()->addDays(60),
        'data_fine_prenotazione' => today()->addDays(65),
    ]);
    $pren->addMedia(
        UploadedFile::fake()->image('delibera.jpg', 10, 10)
    )->toMediaCollection('delibera_consiglio');

    expect(fn () => $this->machine->inviaRichiesta($pren, $this->user))
        ->toThrow(ValidationException::class);
});
