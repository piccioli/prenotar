<?php

declare(strict_types=1);

use App\Enums\ResponsabileTipo;
use App\Settings\GrSettings;
use App\Support\Testing\PrenotazioneWizardAutofill;
use Filament\Forms;
use Illuminate\Support\Carbon;

/** @return array{0: Forms\Set, 1: Forms\Get, 2: Closure(): array<string, mixed>} */
function fakeSetGet(array $initial = []): array
{
    $state = $initial;

    $set = Mockery::mock(Forms\Set::class);
    $set->shouldReceive('__invoke')->andReturnUsing(function (string $key, mixed $value) use (&$state) {
        $state[$key] = $value;

        return $value;
    });

    $get = Mockery::mock(Forms\Get::class);
    $get->shouldReceive('__invoke')->andReturnUsing(fn (string $key = '') => $state[$key] ?? null);

    return [$set, $get, function () use (&$state): array {
        return $state;
    }];
}

it('manuale conferma la lettura del manuale', function (): void {
    [$set, , $stateOf] = fakeSetGet();

    PrenotazioneWizardAutofill::manuale($set);

    expect($stateOf()['manuale_step_confermato'])->toBeTrue();
});

it('quandoDove genera un periodo valido rispettando i giorni minimi di GrSettings', function (): void {
    $grSettings = (new ReflectionClass(GrSettings::class))->newInstanceWithoutConstructor();
    $grSettings->giorni_minimi_caricamento_documenti = 10;
    app()->instance(GrSettings::class, $grSettings);

    [$set, , $stateOf] = fakeSetGet();

    PrenotazioneWizardAutofill::quandoDove($set);
    $state = $stateOf();

    $minimo = today()->addDays($grSettings->giorni_minimi_caricamento_documenti);
    $inizio = Carbon::parse($state['data_inizio_prenotazione']);
    $fine = Carbon::parse($state['data_fine_prenotazione']);

    expect($inizio->greaterThan($minimo))->toBeTrue()
        ->and($inizio->lessThanOrEqualTo($minimo->copy()->addDays(20)))->toBeTrue()
        ->and($fine->greaterThanOrEqualTo($inizio))->toBeTrue()
        ->and($fine->lessThanOrEqualTo($inizio->copy()->addDays(5)))->toBeTrue();
});

it('evento allinea data_inizio_evento a data_inizio_prenotazione quando presente e produce date coerenti', function (): void {
    $dataInizioPrenotazione = today()->addDays(15)->toDateString();
    [$set, $get, $stateOf] = fakeSetGet(['data_inizio_prenotazione' => $dataInizioPrenotazione]);

    PrenotazioneWizardAutofill::evento($set, $get);
    $state = $stateOf();

    $inizioEvento = Carbon::parse($state['data_inizio_evento']);
    $fineEvento = Carbon::parse($state['data_fine_evento']);

    expect($state['data_inizio_evento'])->toBe($dataInizioPrenotazione)
        ->and($fineEvento->greaterThanOrEqualTo($inizioEvento))->toBeTrue()
        ->and($fineEvento->lessThanOrEqualTo($inizioEvento->copy()->addDays(3)))->toBeTrue()
        ->and($state['tipo_evento'])->toBeIn(['fiera', 'manifestazione_cai', 'evento_promozionale', 'corso', 'altro'])
        ->and($state['nome_evento'])->not->toBeEmpty()
        ->and($state['indirizzo_evento'])->not->toBeEmpty();
});

it('evento usa un fallback quando data_inizio_prenotazione non e ancora impostata', function (): void {
    [$set, $get, $stateOf] = fakeSetGet();

    PrenotazioneWizardAutofill::evento($set, $get);

    expect(Carbon::parse($stateOf()['data_inizio_evento'])->greaterThanOrEqualTo(today()))->toBeTrue();
});

it('logisticaTrasporto rispetta il vincolo data_ritiro <= data_inizio_prenotazione', function (): void {
    $dataInizioPrenotazione = today()->addDays(15)->toDateString();
    [$set, $get, $stateOf] = fakeSetGet(['data_inizio_prenotazione' => $dataInizioPrenotazione]);

    PrenotazioneWizardAutofill::logisticaTrasporto($set, $get);
    $state = $stateOf();

    $ritiro = Carbon::parse($state['data_ritiro']);
    $riconsegna = Carbon::parse($state['data_riconsegna']);

    expect($ritiro->lessThanOrEqualTo(Carbon::parse($dataInizioPrenotazione)))->toBeTrue()
        ->and($riconsegna->greaterThanOrEqualTo($ritiro))->toBeTrue()
        ->and($riconsegna->lessThanOrEqualTo($ritiro->copy()->addDays(3)))->toBeTrue()
        ->and($state['targa_autoveicolo'])->toMatch('/^[A-Z]{2}[0-9]{3}[A-Z]{2}$/')
        ->and($state['nome_conducente'])->not->toBeEmpty()
        ->and($state['patente_be_confermata'])->toBeTrue()
        ->and($state['patente_be_dichiarata_at'])->not->toBeNull();
});

it('logisticaTrasporto usa un fallback quando data_inizio_prenotazione non e ancora impostata', function (): void {
    [$set, $get, $stateOf] = fakeSetGet();

    PrenotazioneWizardAutofill::logisticaTrasporto($set, $get);

    expect(Carbon::parse($stateOf()['data_ritiro'])->greaterThanOrEqualTo(today()))->toBeTrue();
});

it('responsabileInLoco genera un tipo valido tra i case di ResponsabileTipo', function (): void {
    [$set, , $stateOf] = fakeSetGet();

    PrenotazioneWizardAutofill::responsabileInLoco($set);
    $state = $stateOf();

    expect($state['responsabile_tipo'])->toBeIn(array_map(fn (ResponsabileTipo $t) => $t->value, ResponsabileTipo::cases()))
        ->and($state['responsabile_nome'])->not->toBeEmpty()
        ->and($state['responsabile_telefono'])->not->toBeEmpty()
        ->and($state['responsabile_email'])->not->toBeEmpty()
        ->and($state['responsabile_titolo_cai'])->not->toBeEmpty();
});
