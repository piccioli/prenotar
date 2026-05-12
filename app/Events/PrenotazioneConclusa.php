<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Prenotazione;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PrenotazioneConclusa
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Prenotazione $prenotazione,
        public readonly ?User $actor,
    ) {}
}
