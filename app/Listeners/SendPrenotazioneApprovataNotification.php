<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrenotazioneApprovata;
use App\Notifications\PrenotazioneApprovataNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPrenotazioneApprovataNotification implements ShouldQueue
{
    public function handle(PrenotazioneApprovata $event): void
    {
        $event->prenotazione->user->notify(
            new PrenotazioneApprovataNotification($event->prenotazione)
        );
    }
}
