<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prenotazione;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderT10Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Prenotazione $prenotazione,
        private readonly int $giorniRimanenti,
    ) {}

    /** @return string[] */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $p = $this->prenotazione;
        $dataInizio = $p->data_inizio_evento->format('d/m/Y');
        $dataScadenza = $p->data_inizio_evento->subDays($this->giorniRimanenti)->format('d/m/Y');

        return (new MailMessage)
            ->subject("[Prenotar] Promemoria: documenti richiesti per {$p->nome_evento}")
            ->greeting('Salve,')
            ->line("La tua prenotazione per l'evento **{$p->nome_evento}** (inizio {$dataInizio}) richiede ancora l'upload del PDF firmato dal Presidente.")
            ->line("Il documento deve essere caricato entro il **{$dataScadenza}** (almeno {$this->giorniRimanenti} giorni prima dell'evento).")
            ->action('Carica il PDF firmato', url('/sezione/prenotazioni/'.$p->id))
            ->line('Se hai già provveduto, ignora questa email.')
            ->salutation('— Piattaforma Prenotar, CAI GR Lombardia');
    }
}
