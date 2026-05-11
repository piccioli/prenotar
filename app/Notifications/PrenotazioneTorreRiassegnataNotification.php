<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prenotazione;
use App\Models\Torre;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrenotazioneTorreRiassegnataNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Prenotazione $prenotazione,
        private readonly int $torreVecchiaId,
    ) {}

    /** @return string[] */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $p = $this->prenotazione;
        $torreNuova = $p->torre !== null ? $p->torre->nome : 'Non specificata';
        $torreVecchiaRecord = Torre::find($this->torreVecchiaId);
        $torreVecchia = $torreVecchiaRecord !== null ? $torreVecchiaRecord->nome : "#{$this->torreVecchiaId}";

        return (new MailMessage)
            ->subject("[Prenotar] Torre riassegnata: {$p->nome_evento}")
            ->greeting('Aggiornamento sulla tua prenotazione')
            ->line("Il GR Lombardia ha riassegnato la torre per la tua prenotazione **{$p->nome_evento}**.")
            ->line("**Torre precedente:** {$torreVecchia}")
            ->line("**Torre assegnata:** {$torreNuova}")
            ->line("**Periodo:** {$p->data_inizio_prenotazione->format('d/m/Y')} — {$p->data_fine_prenotazione->format('d/m/Y')}")
            ->action('Vai alla prenotazione', url('/sezione/prenotazioni/'.$p->id))
            ->salutation('— Piattaforma Prenotar, CAI GR Lombardia');
    }
}
