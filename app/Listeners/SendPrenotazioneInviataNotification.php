<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrenotazioneInviata;
use App\Notifications\PrenotazioneInviataAlGrNotification;
use App\Settings\GrSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendPrenotazioneInviataNotification implements ShouldQueue
{
    public function __construct(private readonly GrSettings $grSettings) {}

    public function handle(PrenotazioneInviata $event): void
    {
        $emails = array_filter(
            $this->grSettings->emails_notifiche_gr,
            fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        );

        if ($emails === []) {
            return;
        }

        Notification::route('mail', $emails)
            ->notify(new PrenotazioneInviataAlGrNotification($event->prenotazione));
    }
}
