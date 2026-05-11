<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prenotazione;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrenotazioneApprovataNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Prenotazione $prenotazione) {}

    /** @return string[] */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $p = $this->prenotazione;
        $torre = $p->torre !== null ? $p->torre->nome : 'Non specificata';

        $message = (new MailMessage)
            ->subject("[Prenotar] Richiesta approvata: {$p->nome_evento}")
            ->greeting('Buone notizie!')
            ->line('La tua richiesta di prenotazione è stata **approvata** dal GR Lombardia.')
            ->line("**Evento:** {$p->nome_evento}")
            ->line("**Torre assegnata:** {$torre}")
            ->line("**Periodo prenotazione:** {$p->data_inizio_prenotazione->format('d/m/Y')} — {$p->data_fine_prenotazione->format('d/m/Y')}");

        if ($p->torre !== null && $p->torre->indirizzo_deposito !== '') {
            $message->line("**Indirizzo deposito torre:** {$p->torre->indirizzo_deposito}");
        }

        $message
            ->line('Per procedere, carica il PDF della richiesta firmato nella tua prenotazione.')
            ->action('Vai alla prenotazione', url('/sezione/prenotazioni/'.$p->id))
            ->salutation('— Piattaforma Prenotar, CAI GR Lombardia');

        return $message;
    }
}
