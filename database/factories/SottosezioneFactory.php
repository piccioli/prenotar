<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sezione;
use App\Models\Sottosezione;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sottosezione>
 */
class SottosezioneFactory extends Factory
{
    protected $model = Sottosezione::class;

    private static int $codiceSequence = 9116001;

    public function definition(): array
    {
        $codice = (string) self::$codiceSequence++;

        return [
            'codice' => $codice,
            'nominativo' => 'S.SEZ. '.strtoupper($this->faker->city()),
            'sezione_id' => Sezione::factory(),
            'codice_sezione' => '9216001',
            'regione' => 'LOMBARDIA',
            'provincia' => $this->faker->randomElement(['MI', 'BG', 'BS', 'CO', 'CR', 'LC', 'LO', 'MN', 'MB', 'PV', 'SO', 'VA']),
            'email' => $this->faker->optional(0.5)->safeEmail(),
            'indirizzo' => $this->faker->address(),
            'is_active' => true,
        ];
    }
}
