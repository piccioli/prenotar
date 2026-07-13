<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/** Verifica che la data di ritiro non sia successiva alla data di inizio della prenotazione. */
class DataRitiroEntroInizioPrenotazione implements ValidationRule
{
    public function __construct(
        private readonly string $dataInizioPrenotazione,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value) || $this->dataInizioPrenotazione === '') {
            return;
        }

        if (Carbon::parse((string) $value)->greaterThan(Carbon::parse($this->dataInizioPrenotazione))) {
            $fail('La data di ritiro non può essere successiva alla data di inizio della prenotazione.');
        }
    }
}
