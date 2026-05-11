<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prenotazione;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrenotazioneInviataAlGrNotification extends Notification implements ShouldQueue
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
        $proprietario = $p->proprietario_label ?? ($p->user->name ?? 'Sezione');
        $torre = $p->torre !== null ? $p->torre->nome : 'Non specificata';

        return (new MailMessage)
            ->subject("[Prenotar] Nuova richiesta: {$p->nome_evento}")
            ->greeting('Nuova richiesta di prenotazione torre')
            ->line("**{$proprietario}** ha inviato una richiesta di prenotazione.")
            ->line("**Evento:** {$p->nome_evento}")
            ->line("**Torre richiesta:** {$torre}")
            ->line("**Periodo prenotazione:** {$p->data_inizio_prenotazione->format('d/m/Y')} — {$p->data_fine_prenotazione->format('d/m/Y')}")
            ->line("**Indirizzo evento:** {$p->indirizzo_evento}")
            ->action('Vedi richiesta nel pannello GR', url('/gr/prenotazioni/'.$p->id))
            ->salutation('— Piattaforma Prenotar, CAI GR Lombardia');
    }
}
