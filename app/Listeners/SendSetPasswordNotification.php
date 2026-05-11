<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserSetPasswordRequested;
use App\Notifications\SetPasswordNotification;

class SendSetPasswordNotification
{
    public function handle(UserSetPasswordRequested $event): void
    {
        $event->user->notify(new SetPasswordNotification($event->user));
    }
}
