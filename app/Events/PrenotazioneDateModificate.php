<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Prenotazione;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PrenotazioneDateModificate
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Prenotazione $prenotazione,
        public readonly ?string $vecchioRitiro,
        public readonly ?string $vecchiaRiconsegna,
        public readonly string $motivo,
    ) {}
}
