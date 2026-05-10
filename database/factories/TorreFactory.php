<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Torre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Torre>
 */
class TorreFactory extends Factory
{
    protected $model = Torre::class;

    public function definition(): array
    {
        return [
            'nome' => 'Torre di arrampicata '.$this->faker->unique()->randomDigitNotNull(),
            'descrizione' => $this->faker->optional()->paragraph(),
            'indirizzo_deposito' => $this->faker->address(),
            'foto_path' => null,
            'specs_tecniche_pdf_path' => null,
            'manuale_pdf_path' => null,
            'is_active' => true,
        ];
    }
}
