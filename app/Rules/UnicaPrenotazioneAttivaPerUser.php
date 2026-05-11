<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Verifica che l'utente non abbia già una prenotazione in stato attivo (Inviata, Approvata, InviatoPdfFirmato, InviatoAssicurazione). */
class UnicaPrenotazioneAttivaPerUser implements ValidationRule
{
    public function __construct(
        private readonly User $user,
        private readonly ?int $excludePrenotazioneId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $stati = [
            PrenotazioneStatus::Inviata->value,
            PrenotazioneStatus::Approvata->value,
            PrenotazioneStatus::InviatoPdfFirmato->value,
            PrenotazioneStatus::InviatoAssicurazione->value,
        ];

        $query = Prenotazione::where('user_id', $this->user->id)
            ->whereIn('status', $stati);

        if ($this->excludePrenotazioneId !== null) {
            $query->where('id', '!=', $this->excludePrenotazioneId);
        }

        if ($query->exists()) {
            $fail('Hai già una prenotazione attiva (inviata o approvata). Attendi la conclusione prima di crearne una nuova.');
        }
    }
}
