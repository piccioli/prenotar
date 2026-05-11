<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrenotazioneRifiutata;
use App\Notifications\PrenotazioneRifiutataNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPrenotazioneRifiutataNotification implements ShouldQueue
{
    public function handle(PrenotazioneRifiutata $event): void
    {
        $event->prenotazione->user->notify(
            new PrenotazioneRifiutataNotification($event->prenotazione, $event->motivo)
        );
    }
}
