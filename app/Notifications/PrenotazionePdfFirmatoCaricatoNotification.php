<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prenotazione;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrenotazionePdfFirmatoCaricatoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Prenotazione $prenotazione,
    ) {}

    /** @return string[] */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $p = $this->prenotazione;

        return (new MailMessage)
            ->subject("[Prenotar] PDF firmato caricato: {$p->nome_evento}")
            ->greeting('PDF firmato disponibile')
            ->line("La sezione {$p->proprietario_label} ha caricato il PDF della richiesta firmato per la prenotazione **{$p->nome_evento}**.")
            ->line("**Torre**: {$p->torre?->nome}")
            ->line("**Periodo**: {$p->data_inizio_prenotazione->format('d/m/Y')} — {$p->data_fine_prenotazione->format('d/m/Y')}")
            ->action('Vai alla prenotazione', url('/gr/prenotazioni/'.$p->id))
            ->salutation('— Piattaforma Prenotar, CAI GR Lombardia');
    }
}
