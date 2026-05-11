<?php

declare(strict_types=1);

use App\Filament\Gr\Resources\TorreResource;
use App\Models\Torre;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('gr_manager può vedere la lista torri', function (): void {
    Torre::factory()->create(['nome' => 'Torre di arrampicata 1', 'is_active' => true]);

    actingAs(User::factory()->grManager()->create())
        ->get(TorreResource::getUrl('index', panel: 'gr'))
        ->assertSuccessful();
});

test('la pagina di lista torri /gr non mostra nessuna action di creazione', function (): void {
    actingAs(User::factory()->grManager()->create())
        ->get(TorreResource::getUrl('index', panel: 'gr'))
        ->assertSuccessful()
        ->assertDontSee('Crea');
});

test('gr_manager può vedere la pagina di dettaglio torre', function (): void {
    $torre = Torre::factory()->create([
        'nome' => 'Torre di arrampicata 1',
        'indirizzo_deposito' => 'Via Assago 1, Assago',
        'is_active' => true,
    ]);

    actingAs(User::factory()->grManager()->create())
        ->get(TorreResource::getUrl('view', ['record' => $torre], panel: 'gr'))
        ->assertSuccessful()
        ->assertSee('Via Assago 1, Assago');
});

test('la pagina di dettaglio /gr mostra l\'indirizzo deposito', function (): void {
    $torre = Torre::factory()->create([
        'indirizzo_deposito' => 'Via Nembro 5, Nembro BG',
        'is_active' => true,
    ]);

    actingAs(User::factory()->grManager()->create())
        ->get(TorreResource::getUrl('view', ['record' => $torre], panel: 'gr'))
        ->assertSuccessful()
        ->assertSee('Via Nembro 5, Nembro BG');
});

test('le torri inattive non appaiono in lista /gr', function (): void {
    Torre::factory()->create(['nome' => 'Torre Attiva', 'is_active' => true]);
    Torre::factory()->create(['nome' => 'Torre Inattiva', 'is_active' => false]);

    actingAs(User::factory()->grManager()->create())
        ->get(TorreResource::getUrl('index', panel: 'gr'))
        ->assertSuccessful()
        ->assertSee('Torre Attiva')
        ->assertDontSee('Torre Inattiva');
});

test('utente sezione non può accedere alle torri del pannello /gr', function (): void {
    actingAs(User::factory()->sezione()->create())
        ->get(TorreResource::getUrl('index', panel: 'gr'))
        ->assertForbidden();
});
