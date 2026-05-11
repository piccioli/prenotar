<?php

declare(strict_types=1);

use App\Filament\Sezione\Resources\TorreResource;
use App\Models\Torre;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('utente sezione può vedere la lista torri', function (): void {
    Torre::factory()->create(['nome' => 'Torre di arrampicata 1', 'is_active' => true]);

    actingAs(User::factory()->sezione()->create())
        ->get(TorreResource::getUrl('index', panel: 'sezione'))
        ->assertSuccessful();
});

test('la pagina di lista torri non mostra nessuna action di creazione', function (): void {
    actingAs(User::factory()->sezione()->create())
        ->get(TorreResource::getUrl('index', panel: 'sezione'))
        ->assertSuccessful()
        ->assertDontSee('Crea');
});

test('utente sezione può vedere la pagina di dettaglio torre', function (): void {
    $torre = Torre::factory()->create([
        'nome' => 'Torre di arrampicata 1',
        'indirizzo_deposito' => 'Via Assago 1, Assago',
        'is_active' => true,
    ]);

    actingAs(User::factory()->sezione()->create())
        ->get(TorreResource::getUrl('view', ['record' => $torre], panel: 'sezione'))
        ->assertSuccessful()
        ->assertSee('Via Assago 1, Assago');
});

test('la pagina di dettaglio mostra l\'indirizzo deposito', function (): void {
    $torre = Torre::factory()->create([
        'indirizzo_deposito' => 'Via Nembro 5, Nembro BG',
        'is_active' => true,
    ]);

    actingAs(User::factory()->sezione()->create())
        ->get(TorreResource::getUrl('view', ['record' => $torre], panel: 'sezione'))
        ->assertSuccessful()
        ->assertSee('Via Nembro 5, Nembro BG');
});

test('le torri inattive non appaiono in lista', function (): void {
    Torre::factory()->create(['nome' => 'Torre Attiva', 'is_active' => true]);
    Torre::factory()->create(['nome' => 'Torre Inattiva', 'is_active' => false]);

    actingAs(User::factory()->sezione()->create())
        ->get(TorreResource::getUrl('index', panel: 'sezione'))
        ->assertSuccessful()
        ->assertSee('Torre Attiva')
        ->assertDontSee('Torre Inattiva');
});
