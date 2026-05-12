<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prenotazione;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderT2ggNotification extends Notification implements ShouldQueue
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
        $sottosezione = $p->sottosezione;
        $sezioneRel = $p->sezione;
        $sezione = $sottosezione !== null
            ? $sottosezione->nominativo
            : ($sezioneRel !== null ? $sezioneRel->nominativo : '(sezione non trovata)');
        $dataRitiro = $p->data_ritiro?->format('d/m/Y') ?? '—';
        $dataEvento = $p->data_inizio_evento->format('d/m/Y');

        return (new MailMessage)
            ->subject("[Prenotar] Promemoria assicurazione: {$sezione} — {$p->nome_evento}")
            ->greeting('Attenzione,')
            ->line("La prenotazione di **{$sezione}** per l'evento **{$p->nome_evento}** (inizio {$dataEvento}) si avvicina alla scadenza per l'invio all'assicurazione.")
            ->line("**Data ritiro torre:** {$dataRitiro}")
            ->line('Il Modulo 3 deve essere inviato all\'assicurazione almeno 48 ore prima del ritiro della torre.')
            ->action('Vai alla prenotazione', url('/gr/prenotazioni/'.$p->id))
            ->line('Accedi alla prenotazione, scarica il Modulo 3 e usa il pulsante "Invia all\'assicurazione".')
            ->salutation('— Piattaforma Prenotar, CAI GR Lombardia');
    }
}
