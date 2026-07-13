<?php

declare(strict_types=1);

use App\Filament\Sezione\Resources\PrenotazioneResource\Pages\CreatePrenotazione;
use App\Models\Prenotazione;
use App\Models\User;
use App\Rules\DataRitiroEntroInizioPrenotazione;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->sezione()->create();
    Filament::setCurrentPanel(Filament::getPanel('sezione'));
});

/** @return array<string, mixed> */
function datiLogisticaTrasportoBase(): array
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

test('nome_conducente è obbligatorio per salvare la prenotazione', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm(datiLogisticaTrasportoBase())
        ->set('data.manuale_step_confermato', true)
        ->set('data.patente_be_confermata', true)
        ->call('create')
        ->assertHasFormErrors(['nome_conducente' => 'required']);

    expect(Prenotazione::count())->toBe(0);
});

test('la dichiarazione patente B+E è obbligatoria per salvare la prenotazione', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiLogisticaTrasportoBase(),
            'nome_conducente' => 'Luigi Bianchi',
        ])
        ->set('data.manuale_step_confermato', true)
        ->call('create')
        ->assertHasFormErrors(['patente_be_confermata' => 'accepted']);

    expect(Prenotazione::count())->toBe(0);
});

test('confermando la dichiarazione patente il timestamp viene valorizzato al salvataggio', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiLogisticaTrasportoBase(),
            'nome_conducente' => 'Luigi Bianchi',
        ])
        ->set('data.manuale_step_confermato', true)
        ->set('data.patente_be_confermata', true)
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->patente_be_dichiarata_at)->not->toBeNull();
});

test('deselezionando la checkbox della patente il timestamp torna a null', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm(datiLogisticaTrasportoBase())
        ->set('data.patente_be_confermata', true)
        ->assertSet('data.patente_be_dichiarata_at', fn (?string $value): bool => filled($value))
        ->set('data.patente_be_confermata', false)
        ->assertSet('data.patente_be_dichiarata_at', null);
});

test('data_ritiro successiva alla data di inizio prenotazione blocca il salvataggio', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiLogisticaTrasportoBase(),
            'nome_conducente' => 'Luigi Bianchi',
            'data_ritiro' => today()->addDays(31)->toDateString(),
        ])
        ->set('data.manuale_step_confermato', true)
        ->set('data.patente_be_confermata', true)
        ->call('create')
        ->assertHasFormErrors(['data_ritiro' => DataRitiroEntroInizioPrenotazione::class]);

    expect(Prenotazione::count())->toBe(0);
});

test('data_ritiro uguale alla data di inizio prenotazione supera la validazione e salva', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiLogisticaTrasportoBase(),
            'nome_conducente' => 'Luigi Bianchi',
            'data_ritiro' => today()->addDays(30)->toDateString(),
        ])
        ->set('data.manuale_step_confermato', true)
        ->set('data.patente_be_confermata', true)
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->data_ritiro->toDateString())->toBe(today()->addDays(30)->toDateString());
});

test('data_ritiro precedente alla data di inizio prenotazione supera la validazione e salva', function (): void {
    actingAs($this->user);

    Livewire::test(CreatePrenotazione::class)
        ->fillForm([
            ...datiLogisticaTrasportoBase(),
            'nome_conducente' => 'Luigi Bianchi',
            'data_ritiro' => today()->addDays(28)->toDateString(),
        ])
        ->set('data.manuale_step_confermato', true)
        ->set('data.patente_be_confermata', true)
        ->call('create')
        ->assertHasNoFormErrors();

    $prenotazione = Prenotazione::sole();
    expect($prenotazione->data_ritiro->toDateString())->toBe(today()->addDays(28)->toDateString());
});

test('il messaggio di errore della regola data_ritiro è esplicativo', function (): void {
    $result = Validator::make(
        ['data_ritiro' => today()->addDays(31)->toDateString()],
        ['data_ritiro' => [new DataRitiroEntroInizioPrenotazione(today()->addDays(30)->toDateString())]],
    );

    expect($result->fails())->toBeTrue()
        ->and($result->errors()->first('data_ritiro'))->toBe('La data di ritiro non può essere successiva alla data di inizio della prenotazione.');
});
