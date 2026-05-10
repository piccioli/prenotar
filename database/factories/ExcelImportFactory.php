<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExcelImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExcelImport>
 */
class ExcelImportFactory extends Factory
{
    protected $model = ExcelImport::class;

    public function definition(): array
    {
        return [
            'filename' => $this->faker->word().'.xlsx',
            'hash' => $this->faker->sha256(),
            'imported_by' => User::factory(),
            'righe_importate' => $this->faker->numberBetween(100, 300),
            'righe_aggiornate' => $this->faker->numberBetween(0, 50),
            'righe_in_errore' => 0,
            'log' => null,
        ];
    }
}
