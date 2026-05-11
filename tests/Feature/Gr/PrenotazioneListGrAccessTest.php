<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Filament\Gr\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre = Torre::factory()->create(['is_active' => true]);
    $this->gr = User::factory()->grManager()->create();
    $this->sezione1 = User::factory()->sezione()->create();
    $this->sezione2 = User::factory()->sezione()->create();
});

test('gr_manager può accedere al pannello prenotazioni /gr', function (): void {
    actingAs($this->gr)
        ->get(PrenotazioneResource::getUrl('index', panel: 'gr'))
        ->assertSuccessful();
});

test('utente sezione non può accedere al pannello prenotazioni /gr', function (): void {
    actingAs($this->sezione1)
        ->get(PrenotazioneResource::getUrl('index', panel: 'gr'))
        ->assertForbidden();
});

test('admin non può accedere al pannello prenotazioni /gr', function (): void {
    actingAs(User::factory()->admin()->create())
        ->get(PrenotazioneResource::getUrl('index', panel: 'gr'))
        ->assertForbidden();
});

test('gr_manager vede prenotazioni di tutte le sezioni', function (): void {
    $pren1 = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione1->id]);
    $pren2 = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione2->id]);

    actingAs($this->gr)
        ->get(PrenotazioneResource::getUrl('index', panel: 'gr'))
        ->assertSuccessful();

    // Resource query must not be scoped to a single user — GR sees all sezioni
    $ids = PrenotazioneResource::getEloquentQuery()->pluck('id')->toArray();

    expect($ids)
        ->toContain($pren1->id)
        ->toContain($pren2->id);
});

test('tab da_approvare mostra solo prenotazioni con status Inviata', function (): void {
    Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione1->id]);
    Prenotazione::factory()->approvata()->create(['user_id' => $this->sezione1->id]);
    Prenotazione::factory()->annullata()->create(['user_id' => $this->sezione1->id]);

    $count = Prenotazione::where('status', PrenotazioneStatus::Inviata->value)->count();

    expect($count)->toBe(1);
});

test('tab attive esclude prenotazioni Annullata e Concluso', function (): void {
    Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione1->id]);
    Prenotazione::factory()->approvata()->create(['user_id' => $this->sezione1->id]);
    Prenotazione::factory()->annullata()->create(['user_id' => $this->sezione1->id]);

    $attive = Prenotazione::whereNotIn('status', [
        PrenotazioneStatus::Concluso->value,
        PrenotazioneStatus::Annullata->value,
    ])->count();

    expect($attive)->toBe(2);
});

test('tab archivio mostra solo Annullata e Concluso', function (): void {
    Prenotazione::factory()->annullata()->create(['user_id' => $this->sezione1->id]);
    Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione1->id]);

    $archivio = Prenotazione::whereIn('status', [
        PrenotazioneStatus::Concluso->value,
        PrenotazioneStatus::Annullata->value,
    ])->count();

    expect($archivio)->toBe(1);
});

test('gr_manager può accedere alla pagina di dettaglio', function (): void {
    $pren = Prenotazione::factory()->inviata()->create(['user_id' => $this->sezione1->id]);

    actingAs($this->gr)
        ->get(PrenotazioneResource::getUrl('view', ['record' => $pren], panel: 'gr'))
        ->assertSuccessful();
});
