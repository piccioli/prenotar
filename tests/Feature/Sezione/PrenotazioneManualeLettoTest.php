<?php

declare(strict_types=1);

use App\Filament\Sezione\Resources\PrenotazioneResource\Pages\CreatePrenotazione;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->sezione()->create();
    Filament::setCurrentPanel(Filament::getPanel('sezione'));
});

/** @return array<string, mixed> */
function datiPrenotazioneManualeLettoBase(): array
{
    return [
        'data_inizio_prenotazione' => today()->addDays(30)->toDateString(),
        'data_fine_prenotazione' => today()->addDays(35)->toDateString(),
        'nome_evento' => 'Evento di test',
        'tipo_evento' => 'corso',
        'indirizzo_evento' => 'Via Roma 1',
        'data_inizio_evento' => today()->addDays(31)->toDateString(),
        'data_fine_evento' => today()->addDays(34)->toDateString(),
        'responsabile_nome' => 'Mario Rossi',
        'responsabile_tipo' => 'istruttore',
        'responsabile_telefono' => '0123456789',
        'responsabile_email' => 'mario@example.com',
    ];
}

test('con torre selezionata il wizard non può essere inviato senza spuntare la conferma di lettura del manuale', function (): void {
    actingAs($this->user);
    $torre = Torre::factory()->create(['is_active' => true]);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiPrenotazioneManualeLettoBase(),
            'torre_id' => $torre->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['manuale_letto_confirm' => 'accepted']);

    expect(Prenotazione::count())->toBe(0);
});

test('con torre selezionata e conferma di lettura spuntata il wizard salva la prenotazione con audit del manuale', function (): void {
    actingAs($this->user);
    $torre = Torre::factory()->create(['is_active' => true]);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiPrenotazioneManualeLettoBase(),
            'torre_id' => $torre->id,
        ])
        ->set('data.manuale_letto_confirm', true)
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->torre_id)->toBe($torre->id)
        ->and($prenotazione->manuale_letto_torre_id)->toBe($torre->id)
        ->and($prenotazione->manuale_letto_confermato_at)->not->toBeNull();
});

test('senza torre selezionata il wizard non richiede la conferma di lettura del manuale', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm(datiPrenotazioneManualeLettoBase())
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->torre_id)->toBeNull()
        ->and($prenotazione->manuale_letto_torre_id)->toBeNull()
        ->and($prenotazione->manuale_letto_confermato_at)->toBeNull();
});

test('cambiare la torre selezionata dopo la conferma resetta il flag e richiede una nuova conferma', function (): void {
    actingAs($this->user);
    $torre1 = Torre::factory()->create(['is_active' => true]);
    $torre2 = Torre::factory()->create(['is_active' => true]);

    $component = Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiPrenotazioneManualeLettoBase(),
            'torre_id' => $torre1->id,
        ])
        ->set('data.manuale_letto_confirm', true)
        ->assertSet('data.manuale_letto_torre_id', $torre1->id);

    $component->set('data.torre_id', $torre2->id)
        ->assertSet('data.manuale_letto_confirm', false)
        ->assertSet('data.manuale_letto_confermato_at', null)
        ->assertSet('data.manuale_letto_torre_id', null)
        ->call('create')
        ->assertHasFormErrors(['manuale_letto_confirm' => 'accepted']);

    expect(Prenotazione::count())->toBe(0);
});
