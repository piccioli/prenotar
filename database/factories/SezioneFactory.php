<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sezione;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sezione>
 */
class SezioneFactory extends Factory
{
    protected $model = Sezione::class;

    private static int $codiceSequence = 9216001;

    public function definition(): array
    {
        $codice = (string) self::$codiceSequence++;

        return [
            'codice' => $codice,
            'nominativo' => 'SEZ. '.strtoupper($this->faker->city()),
            'regione' => 'LOMBARDIA',
            'provincia' => $this->faker->randomElement(['MI', 'BG', 'BS', 'CO', 'CR', 'LC', 'LO', 'MN', 'MB', 'PV', 'SO', 'VA']),
            'email' => $this->faker->safeEmail(),
            'pec' => null,
            'sito_web' => null,
            'telefono' => $this->faker->phoneNumber(),
            'indirizzo' => $this->faker->address(),
            'iscritti_count' => $this->faker->numberBetween(50, 2000),
            'presidente_nome' => $this->faker->name(),
            'anno_fondazione' => $this->faker->numberBetween(1900, 2000),
            'ente_terzo_settore' => false,
            'is_active' => true,
        ];
    }
}
