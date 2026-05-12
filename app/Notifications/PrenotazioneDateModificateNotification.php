<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prenotazione;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrenotazioneDateModificateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Prenotazione $prenotazione,
        private readonly ?string $vecchioRitiro,
        private readonly ?string $vecchiaRiconsegna,
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
        $fmt = fn (?string $d): string => $d !== null ? Carbon::parse($d)->format('d/m/Y') : '—';

        $nuovoRitiro = $p->data_ritiro?->format('d/m/Y') ?? '—';
        $nuovaRiconsegna = $p->data_riconsegna?->format('d/m/Y') ?? '—';

        return (new MailMessage)
            ->subject("[Prenotar] Modifica date trasporto: {$p->nome_evento}")
            ->greeting('Aggiornamento sulla tua prenotazione')
            ->line("Il GR Lombardia ha modificato le date di trasporto per la prenotazione **{$p->nome_evento}**.")
            ->line("**Data ritiro:** {$fmt($this->vecchioRitiro)} → **{$nuovoRitiro}**")
            ->line("**Data riconsegna:** {$fmt($this->vecchiaRiconsegna)} → **{$nuovaRiconsegna}**")
            ->line("**Motivo:** {$this->motivo}")
            ->action('Vai alla prenotazione', url('/sezione/prenotazioni/'.$p->id))
            ->salutation('— Piattaforma Prenotar, CAI GR Lombardia');
    }
}
