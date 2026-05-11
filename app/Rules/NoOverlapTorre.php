<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Verifica che non ci siano prenotazioni sovrapposte nella stessa torre (stati ≥ Inviata). */
class NoOverlapTorre implements ValidationRule
{
    public function __construct(
        private readonly ?int $torreId,
        private readonly string $dataInizio,
        private readonly string $dataFine,
        private readonly ?int $excludePrenotazioneId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->torreId === null || $this->dataInizio === '' || $this->dataFine === '') {
            return;
        }

        $stati = [
            PrenotazioneStatus::Inviata->value,
            PrenotazioneStatus::Approvata->value,
            PrenotazioneStatus::InviatoPdfFirmato->value,
            PrenotazioneStatus::InviatoAssicurazione->value,
        ];

        $query = Prenotazione::where('torre_id', $this->torreId)
            ->whereIn('status', $stati)
            ->where('data_inizio_prenotazione', '<=', $this->dataFine)
            ->where('data_fine_prenotazione', '>=', $this->dataInizio);

        if ($this->excludePrenotazioneId !== null) {
            $query->where('id', '!=', $this->excludePrenotazioneId);
        }

        if ($query->exists()) {
            $fail('Le date selezionate si sovrappongono a una prenotazione già inviata o approvata per questa torre. Scegli un altro periodo o una torre diversa.');
        }
    }
}
