<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrenotazioneInviataAssicurazione;
use App\Mail\Modulo3Mail;
use App\Settings\GrSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendModulo3ToAssicurazione implements ShouldQueue
{
    public function __construct(private readonly GrSettings $grSettings) {}

    public function handle(PrenotazioneInviataAssicurazione $event): void
    {
        Mail::send(new Modulo3Mail($event->prenotazione, $this->grSettings));
    }
}
