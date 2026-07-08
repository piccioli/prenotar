<?php

declare(strict_types=1);

use App\Enums\CategoriaPatente;
use App\Enums\TipoMezzo;
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
    Torre::factory()->create(['is_active' => true]);
    $this->user = User::factory()->sezione()->create();
    Filament::setCurrentPanel(Filament::getPanel('sezione'));
});

/** @return array<string, mixed> */
function datiPrenotazioneValidiBase(): array
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

test('mezzo privato senza categoria patente fallisce la validazione', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiPrenotazioneValidiBase(),
            'tipo_mezzo' => TipoMezzo::Privato->value,
            'categoria_patente_privato' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['categoria_patente_privato' => 'required']);

    expect(Prenotazione::count())->toBe(0);
});

test('mezzo privato con categoria patente selezionata salva correttamente', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiPrenotazioneValidiBase(),
            'tipo_mezzo' => TipoMezzo::Privato->value,
            'categoria_patente_privato' => CategoriaPatente::BE->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->tipo_mezzo)->toBe(TipoMezzo::Privato)
        ->and($prenotazione->categoria_patente_privato)->toBe(CategoriaPatente::BE);
});

test('mezzo aziendale (default) non richiede categoria patente', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm(datiPrenotazioneValidiBase())
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->tipo_mezzo)->toBe(TipoMezzo::Aziendale)
        ->and($prenotazione->categoria_patente_privato)->toBeNull();
});
