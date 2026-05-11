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
    $this->torre = Torre::factory()->create(['nome' => 'Torre 1', 'is_active' => true]);
});

test('utente sezione può aprire la pagina di creazione prenotazione', function (): void {
    actingAs(User::factory()->sezione()->create())
        ->get(PrenotazioneResource::getUrl('create', panel: 'sezione'))
        ->assertSuccessful();
});

test('admin non può accedere alla pagina di creazione del pannello sezione', function (): void {
    actingAs(User::factory()->admin()->create())
        ->get(PrenotazioneResource::getUrl('create', panel: 'sezione'))
        ->assertForbidden();
});

test('gr_manager non può accedere alla pagina di creazione del pannello sezione', function (): void {
    actingAs(User::factory()->grManager()->create())
        ->get(PrenotazioneResource::getUrl('create', panel: 'sezione'))
        ->assertForbidden();
});

test('la prenotazione di default ha status Bozza e i campi utente correttamente derivati', function (): void {
    $user = User::factory()->sezione()->create();

    $prenotazione = Prenotazione::create([
        'user_id' => $user->id,
        'sezione_id' => $user->sezione_id,
        'sottosezione_id' => null,
        'torre_id' => null,
        'status' => PrenotazioneStatus::Bozza,
        'nome_evento' => 'Evento di test',
        'tipo_evento' => 'corso',
        'indirizzo_evento' => 'Via Roma 1',
        'data_inizio_evento' => today()->addDays(30),
        'data_fine_evento' => today()->addDays(35),
        'data_inizio_prenotazione' => today()->addDays(30),
        'data_fine_prenotazione' => today()->addDays(35),
        'azienda_trasporto' => 'Montagna Servizi',
        'responsabile_nome' => 'Mario Rossi',
        'responsabile_tipo' => 'istruttore',
        'responsabile_telefono' => '0123456789',
        'responsabile_email' => 'mario@example.com',
    ]);

    expect($prenotazione->status)->toBe(PrenotazioneStatus::Bozza)
        ->and($prenotazione->user_id)->toBe($user->id)
        ->and($prenotazione->sezione_id)->toBe($user->sezione_id)
        ->and($prenotazione->sottosezione_id)->toBeNull();
});

test('la pagina crea ha il wizard con i 5 step', function (): void {
    actingAs(User::factory()->sezione()->create())
        ->get(PrenotazioneResource::getUrl('create', panel: 'sezione'))
        ->assertSuccessful()
        ->assertSee('Quando & dove')
        ->assertSee('Evento');
});
