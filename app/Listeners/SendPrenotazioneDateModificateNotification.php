<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrenotazioneDateModificate;
use App\Notifications\PrenotazioneDateModificateNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPrenotazioneDateModificateNotification implements ShouldQueue
{
    public function handle(PrenotazioneDateModificate $event): void
    {
        $event->prenotazione->user->notify(
            new PrenotazioneDateModificateNotification(
                $event->prenotazione,
                $event->vecchioRitiro,
                $event->vecchiaRiconsegna,
                $event->motivo,
            )
        );
    }
}
