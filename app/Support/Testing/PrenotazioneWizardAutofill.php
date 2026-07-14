<?php

declare(strict_types=1);

namespace App\Support\Testing;

use App\Enums\ResponsabileTipo;
use App\Settings\GrSettings;
use Filament\Forms;
use Illuminate\Support\Carbon;

/** Genera dati di test validi (Faker it_IT) per il bottone di autofill dev-only del wizard prenotazione. */
class PrenotazioneWizardAutofill
{
    public static function manuale(Forms\Set $set): void
    {
        $set('manuale_step_confermato', true);
    }

    public static function quandoDove(Forms\Set $set): void
    {
        $faker = fake('it_IT');

        $inizio = today()
            ->addDays(app(GrSettings::class)->giorni_minimi_caricamento_documenti)
            ->addDays($faker->numberBetween(1, 20));
        $fine = $inizio->copy()->addDays($faker->numberBetween(1, 5));

        $set('data_inizio_prenotazione', $inizio->toDateString());
        $set('data_fine_prenotazione', $fine->toDateString());
    }

    public static function evento(Forms\Set $set, Forms\Get $get): void
    {
        $faker = fake('it_IT');

        $inizio = self::dataInizioPrenotazioneOFallback($get);
        $fine = $inizio->copy()->addDays($faker->numberBetween(1, 3));

        $set('nome_evento', $faker->sentence(3));
        $set('descrizione_evento', $faker->paragraph());
        $set('indirizzo_evento', $faker->address());
        $set('tipo_evento', $faker->randomElement(['fiera', 'manifestazione_cai', 'evento_promozionale', 'corso', 'altro']));
        $set('data_inizio_evento', $inizio->toDateString());
        $set('data_fine_evento', $fine->toDateString());
    }

    public static function logisticaTrasporto(Forms\Set $set, Forms\Get $get): void
    {
        $faker = fake('it_IT');

        $ritiro = self::dataInizioPrenotazioneOFallback($get);
        $riconsegna = $ritiro->copy()->addDays($faker->numberBetween(1, 3));

        $set('data_ritiro', $ritiro->toDateString());
        $set('data_riconsegna', $riconsegna->toDateString());
        $set('targa_autoveicolo', $faker->regexify('[A-Z]{2}[0-9]{3}[A-Z]{2}'));
        $set('nome_conducente', $faker->name());
        $set('patente_be_confermata', true);
        $set('patente_be_dichiarata_at', now());
    }

    public static function responsabileInLoco(Forms\Set $set): void
    {
        $faker = fake('it_IT');

        $set('responsabile_nome', $faker->name());
        $set('responsabile_telefono', $faker->phoneNumber());
        $set('responsabile_email', $faker->safeEmail());
        $set('responsabile_tipo', $faker->randomElement(ResponsabileTipo::cases())->value);
        $set('responsabile_titolo_cai', $faker->jobTitle());
    }

    private static function dataInizioPrenotazioneOFallback(Forms\Get $get): Carbon
    {
        $dataInizioPrenotazione = $get('data_inizio_prenotazione');

        return filled($dataInizioPrenotazione)
            ? Carbon::parse($dataInizioPrenotazione)
            : today()->addDays(10);
    }
}
