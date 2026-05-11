<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrenotazioneTorreRiassegnata;
use App\Notifications\PrenotazioneTorreRiassegnataNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPrenotazioneTorreRiassegnataNotification implements ShouldQueue
{
    public function handle(PrenotazioneTorreRiassegnata $event): void
    {
        $event->prenotazione->user->notify(
            new PrenotazioneTorreRiassegnataNotification($event->prenotazione, $event->torreVecchiaId)
        );
    }
}
