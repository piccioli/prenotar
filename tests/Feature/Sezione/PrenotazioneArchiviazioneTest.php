<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
});

test('la tab attive mostra solo prenotazioni non finali', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->create(['user_id' => $user->id, 'status' => PrenotazioneStatus::Bozza]);
    Prenotazione::factory()->inviata()->create(['user_id' => $user->id]);
    Prenotazione::factory()->approvata()->create(['user_id' => $user->id]);
    Prenotazione::factory()->annullata()->create(['user_id' => $user->id]);
    Prenotazione::factory()->create(['user_id' => $user->id, 'status' => PrenotazioneStatus::Concluso]);

    actingAs($user)
        ->get(PrenotazioneResource::getUrl('index', panel: 'sezione').'?activeTab=attive')
        ->assertSuccessful();

    // Il tab attive esclude Concluso e Annullata
    $attive = Prenotazione::query()
        ->where('user_id', $user->id)
        ->whereNotIn('status', [PrenotazioneStatus::Concluso->value, PrenotazioneStatus::Annullata->value])
        ->count();
    expect($attive)->toBe(3);
});

test('la tab archivio mostra solo concluse e annullate', function (): void {
    $user = User::factory()->sezione()->create();
    Prenotazione::factory()->create(['user_id' => $user->id, 'status' => PrenotazioneStatus::Bozza]);
    Prenotazione::factory()->annullata()->create(['user_id' => $user->id]);
    Prenotazione::factory()->create(['user_id' => $user->id, 'status' => PrenotazioneStatus::Concluso]);

    $archiviate = Prenotazione::query()
        ->where('user_id', $user->id)
        ->whereIn('status', [PrenotazioneStatus::Concluso->value, PrenotazioneStatus::Annullata->value])
        ->count();
    expect($archiviate)->toBe(2);
});

test('il default sort è data_inizio_prenotazione DESC', function (): void {
    $user = User::factory()->sezione()->create();

    $prima = Prenotazione::factory()->create([
        'user_id' => $user->id,
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
    ]);
    $seconda = Prenotazione::factory()->create([
        'user_id' => $user->id,
        'data_inizio_prenotazione' => today()->addDays(60),
        'data_fine_prenotazione' => today()->addDays(65),
    ]);

    $this->actingAs($user);
    $risultati = PrenotazioneResource::getEloquentQuery()->pluck('id')->toArray();

    // La più futura (addDays 60) deve venire prima
    expect($risultati)->toHaveCount(2)
        ->and($risultati[0])->toBe($seconda->id)
        ->and($risultati[1])->toBe($prima->id);
});

test('le prenotazioni di altri utenti non appaiono', function (): void {
    $utente1 = User::factory()->sezione()->create();
    $utente2 = User::factory()->sezione()->create();

    Prenotazione::factory()->create(['user_id' => $utente1->id]);
    Prenotazione::factory()->create(['user_id' => $utente2->id]);

    $this->actingAs($utente1);
    $count = PrenotazioneResource::getEloquentQuery()->count();
    expect($count)->toBe(1);
});
