<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrenotazioneHistory>
 */
class PrenotazioneHistoryFactory extends Factory
{
    protected $model = PrenotazioneHistory::class;

    public function definition(): array
    {
        return [
            'prenotazione_id' => Prenotazione::factory(),
            'user_id' => User::factory(),
            'status_from' => PrenotazioneStatus::Bozza,
            'status_to' => PrenotazioneStatus::Inviata,
            'note' => null,
            'created_at' => now(),
        ];
    }
}
