<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PrenotazioneStatus;
use App\Enums\ResponsabileTipo;
use App\Models\Prenotazione;
use App\Models\Sezione;
use App\Models\Torre;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prenotazione>
 */
class PrenotazioneFactory extends Factory
{
    protected $model = Prenotazione::class;

    public function definition(): array
    {
        $dataInizioPren = Carbon::instance($this->faker->dateTimeBetween('+1 month', '+6 months'));
        $dataFinePren = (clone $dataInizioPren)->addDays($this->faker->numberBetween(1, 5));
        $dataIniziEvento = (clone $dataInizioPren)->subDays(1);
        $dataFineEvento = (clone $dataFinePren)->addDays(1);

        return [
            'user_id' => User::factory(),
            'sezione_id' => Sezione::factory(),
            'sottosezione_id' => null,
            'torre_id' => Torre::factory(),
            'nome_evento' => $this->faker->sentence(3),
            'tipo_evento' => $this->faker->randomElement(['Arrampicata', 'Corso', 'Evento CAI', 'Giornata aperta']),
            'descrizione_evento' => $this->faker->optional()->paragraph(),
            'indirizzo_evento' => $this->faker->address(),
            'data_inizio_evento' => $dataIniziEvento,
            'data_fine_evento' => $dataFineEvento,
            'data_inizio_prenotazione' => $dataInizioPren,
            'data_fine_prenotazione' => $dataFinePren,
            'data_ritiro' => (clone $dataInizioPren)->subDay(),
            'luogo_ritiro' => $this->faker->address(),
            'data_riconsegna' => (clone $dataFinePren)->addDay(),
            'luogo_riconsegna' => $this->faker->address(),
            'azienda_trasporto' => 'montagna servizi',
            'targa_autoveicolo' => null,
            'responsabile_nome' => $this->faker->name(),
            'responsabile_titolo_cai' => null,
            'responsabile_codice_cai' => null,
            'responsabile_telefono' => $this->faker->phoneNumber(),
            'responsabile_email' => $this->faker->safeEmail(),
            'responsabile_tipo' => ResponsabileTipo::Istruttore,
            'status' => PrenotazioneStatus::Bozza,
            'approvato_da' => null,
            'approvato_at' => null,
            'motivo_rifiuto' => null,
            'pdf_firmato_at' => null,
            'pdf_firmato_path' => null,
            'inviato_assicurazione_at' => null,
            'concluso_at' => null,
            'archived_at' => null,
        ];
    }

    public function inviata(): static
    {
        return $this->state(['status' => PrenotazioneStatus::Inviata]);
    }

    public function approvata(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PrenotazioneStatus::Approvata,
            'approvato_at' => now(),
        ]);
    }

    public function annullata(): static
    {
        return $this->state([
            'status' => PrenotazioneStatus::Annullata,
            'motivo_rifiuto' => 'Annullata per test',
            'archived_at' => now(),
        ]);
    }

    public function inviatoPdfFirmato(): static
    {
        return $this->state([
            'status' => PrenotazioneStatus::InviatoPdfFirmato,
            'approvato_at' => now(),
            'pdf_firmato_at' => now(),
            'pdf_firmato_path' => 'fake/pdf-firmato.pdf',
        ]);
    }
}
