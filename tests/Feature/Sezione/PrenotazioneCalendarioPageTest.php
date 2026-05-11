<?php

declare(strict_types=1);

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Pages\CalendarioPage;
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
    $this->torre1 = Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
    $this->torre2 = Torre::factory()->create(['nome' => 'Torre 2', 'is_active' => true]);
});

test('utente sezione può accedere alla pagina calendario', function (): void {
    actingAs(User::factory()->sezione()->create())
        ->get(CalendarioPage::getUrl(panel: 'sezione'))
        ->assertSuccessful();
});

test('admin non può accedere alla pagina calendario del pannello sezione', function (): void {
    actingAs(User::factory()->admin()->create())
        ->get(CalendarioPage::getUrl(panel: 'sezione'))
        ->assertForbidden();
});

test('gr_manager non può accedere alla pagina calendario del pannello sezione', function (): void {
    actingAs(User::factory()->grManager()->create())
        ->get(CalendarioPage::getUrl(panel: 'sezione'))
        ->assertForbidden();
});

test('eventiCalendarioPubblico restituisce solo prenotazioni in stati >= Inviata', function (): void {
    $user = User::factory()->sezione()->create();

    Prenotazione::factory()->create([
        'user_id' => $user->id,
        'status' => PrenotazioneStatus::Bozza,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);
    Prenotazione::factory()->inviata()->create([
        'user_id' => $user->id,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);
    Prenotazione::factory()->approvata()->create([
        'user_id' => $user->id,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);
    Prenotazione::factory()->annullata()->create([
        'user_id' => $user->id,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);
    Prenotazione::factory()->create([
        'user_id' => $user->id,
        'status' => PrenotazioneStatus::Concluso,
        'data_inizio_prenotazione' => '2027-10-10',
        'data_fine_prenotazione' => '2027-10-12',
    ]);

    $eventi = Prenotazione::eventiCalendarioPubblico(
        Carbon::parse('2027-10-01'),
        Carbon::parse('2027-10-31'),
    );

    expect($eventi)->toHaveCount(2);
    expect($eventi->every(fn (Prenotazione $p) => in_array($p->status, [
        PrenotazioneStatus::Inviata,
        PrenotazioneStatus::Approvata,
    ])))->toBeTrue();
});

test('eventiCalendarioPubblico restituisce prenotazioni di tutte le sezioni', function (): void {
    $user1 = User::factory()->sezione()->create();
    $user2 = User::factory()->sezione()->create();

    Prenotazione::factory()->inviata()->create([
        'user_id' => $user1->id,
        'data_inizio_prenotazione' => '2027-11-10',
        'data_fine_prenotazione' => '2027-11-12',
    ]);
    Prenotazione::factory()->inviata()->create([
        'user_id' => $user2->id,
        'data_inizio_prenotazione' => '2027-11-15',
        'data_fine_prenotazione' => '2027-11-17',
    ]);

    $eventi = Prenotazione::eventiCalendarioPubblico(
        Carbon::parse('2027-11-01'),
        Carbon::parse('2027-11-30'),
    );

    expect($eventi)->toHaveCount(2);
});

test('eventiCalendarioPubblico filtra per torre_id', function (): void {
    $user = User::factory()->sezione()->create();

    Prenotazione::factory()->inviata()->create([
        'user_id' => $user->id,
        'torre_id' => $this->torre1->id,
        'data_inizio_prenotazione' => '2027-12-01',
        'data_fine_prenotazione' => '2027-12-05',
    ]);
    Prenotazione::factory()->inviata()->create([
        'user_id' => $user->id,
        'torre_id' => $this->torre2->id,
        'data_inizio_prenotazione' => '2027-12-10',
        'data_fine_prenotazione' => '2027-12-15',
    ]);

    $eventi = Prenotazione::eventiCalendarioPubblico(
        Carbon::parse('2027-12-01'),
        Carbon::parse('2027-12-31'),
        $this->torre1->id,
    );

    expect($eventi)->toHaveCount(1)
        ->and($eventi->first()?->torre_id)->toBe($this->torre1->id);
});

test('eventiCalendarioPubblico non restituisce eventi fuori dal range', function (): void {
    $user = User::factory()->sezione()->create();

    Prenotazione::factory()->inviata()->create([
        'user_id' => $user->id,
        'data_inizio_prenotazione' => '2027-08-01',
        'data_fine_prenotazione' => '2027-08-05',
    ]);

    $eventi = Prenotazione::eventiCalendarioPubblico(
        Carbon::parse('2027-10-01'),
        Carbon::parse('2027-10-31'),
    );

    expect($eventi)->toHaveCount(0);
});
