<?php

declare(strict_types=1);

use App\Filament\Gr\Pages\CalendarioPage;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->torre = Torre::factory()->create(['is_active' => true]);
});

test('gr_manager può accedere alla pagina calendario', function (): void {
    actingAs(User::factory()->grManager()->create())
        ->get(CalendarioPage::getUrl(panel: 'gr'))
        ->assertSuccessful();
});

test('utente sezione non può accedere alla pagina calendario /gr', function (): void {
    actingAs(User::factory()->sezione()->create())
        ->get(CalendarioPage::getUrl(panel: 'gr'))
        ->assertForbidden();
});

test('admin non può accedere alla pagina calendario /gr', function (): void {
    actingAs(User::factory()->admin()->create())
        ->get(CalendarioPage::getUrl(panel: 'gr'))
        ->assertForbidden();
});

test('eventiCalendarioPubblico restituisce prenotazioni di tutte le sezioni in stati >= Inviata', function (): void {
    $sezione1 = User::factory()->sezione()->create();
    $sezione2 = User::factory()->sezione()->create();

    Prenotazione::factory()->inviata()->create([
        'user_id' => $sezione1->id,
        'data_inizio_prenotazione' => '2027-11-10',
        'data_fine_prenotazione' => '2027-11-12',
    ]);
    Prenotazione::factory()->approvata()->create([
        'user_id' => $sezione2->id,
        'data_inizio_prenotazione' => '2027-11-15',
        'data_fine_prenotazione' => '2027-11-17',
    ]);
    // Bozza non deve apparire
    Prenotazione::factory()->create([
        'user_id' => $sezione1->id,
        'data_inizio_prenotazione' => '2027-11-20',
        'data_fine_prenotazione' => '2027-11-22',
    ]);

    $eventi = Prenotazione::eventiCalendarioPubblico(
        Carbon::parse('2027-11-01'),
        Carbon::parse('2027-11-30'),
    );

    expect($eventi)->toHaveCount(2);
});
