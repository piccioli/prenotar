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
        'nome_conducente' => 'Luigi Bianchi',
        'patente_be_confermata' => true,
    ];
}

test('senza confermare la checkbox del primo step il wizard non può essere inviato, con una torre selezionata', function (): void {
    actingAs($this->user);
    $torre = Torre::factory()->create(['is_active' => true]);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiPrenotazioneManualeLettoBase(),
            'torre_id' => $torre->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['manuale_step_confermato' => 'accepted']);

    expect(Prenotazione::count())->toBe(0);
});

test('senza confermare la checkbox del primo step il wizard non può essere inviato, senza torre selezionata (nessuna preferenza)', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm(datiPrenotazioneManualeLettoBase())
        ->call('create')
        ->assertHasFormErrors(['manuale_step_confermato' => 'accepted']);

    expect(Prenotazione::count())->toBe(0);
});

test('con la conferma di lettura spuntata e una torre selezionata il wizard salva la prenotazione', function (): void {
    actingAs($this->user);
    $torre = Torre::factory()->create(['is_active' => true]);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiPrenotazioneManualeLettoBase(),
            'torre_id' => $torre->id,
        ])
        ->set('data.manuale_step_confermato', true)
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->torre_id)->toBe($torre->id)
        ->and($prenotazione->manuale_letto_confermato_at)->not->toBeNull();
});

test('con la conferma di lettura spuntata e nessuna torre selezionata (nessuna preferenza) il wizard salva la prenotazione', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm(datiPrenotazioneManualeLettoBase())
        ->set('data.manuale_step_confermato', true)
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->torre_id)->toBeNull()
        ->and($prenotazione->manuale_letto_confermato_at)->not->toBeNull();
});

test('la conferma del manuale è indipendente dalla torre scelta: manuale_letto_torre_id riflette la torre di riferimento, non quella selezionata', function (): void {
    actingAs($this->user);
    $torreConManuale = Torre::factory()->create(['is_active' => true, 'manuale_pdf_path' => 'torri/manuale.pdf']);
    $torreScelta = Torre::factory()->create(['is_active' => true]);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiPrenotazioneManualeLettoBase(),
            'torre_id' => $torreScelta->id,
        ])
        ->set('data.manuale_step_confermato', true)
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->torre_id)->toBe($torreScelta->id)
        ->and($prenotazione->manuale_letto_torre_id)->toBe($torreConManuale->id)
        ->and($prenotazione->manuale_letto_torre_id)->not->toBe($prenotazione->torre_id);
});

test('quando nessuna torre attiva ha il manuale caricato lo step mostra un avviso ma permette comunque di procedere', function (): void {
    actingAs($this->user);
    Torre::factory()->create(['is_active' => true, 'manuale_pdf_path' => null]);

    Livewire::test(CreatePrenotazione::class)
        ->assertSee('Manuale non ancora disponibile.')
        ->fillForm(datiPrenotazioneManualeLettoBase())
        ->set('data.manuale_step_confermato', true)
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->manuale_letto_torre_id)->toBeNull()
        ->and($prenotazione->manuale_letto_confermato_at)->not->toBeNull();
});
