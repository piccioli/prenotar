<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prenotazione;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrenotazioneRifiutataNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Prenotazione $prenotazione,
        private readonly string $motivo,
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
            ->subject("[Prenotar] Richiesta rifiutata: {$p->nome_evento}")
            ->greeting('Aggiornamento sulla tua richiesta')
            ->line("La tua richiesta di prenotazione per **{$p->nome_evento}** è stata **rifiutata** dal GR Lombardia.")
            ->line("**Periodo:** {$p->data_inizio_prenotazione->format('d/m/Y')} — {$p->data_fine_prenotazione->format('d/m/Y')}")
            ->line("**Motivazione:** {$this->motivo}")
            ->line('Per ulteriori informazioni contatta il GR Lombardia.')
            ->action('Vai alla prenotazione', url('/sezione/prenotazioni/'.$p->id))
            ->salutation('— Piattaforma Prenotar, CAI GR Lombardia');
    }
}
