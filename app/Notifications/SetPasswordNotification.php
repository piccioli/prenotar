<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Settings\GrSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class SetPasswordNotification extends Notification
{
    use Queueable;

    private string $token;

    public function __construct(private readonly User $user)
    {
        $this->token = Password::createToken($user);
    }

    /** @return string[] */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $panelRoute = match (true) {
            $this->user->isAdmin() => 'filament.admin.auth.password-reset.reset',
            $this->user->isGrManager() => 'filament.gr.auth.password-reset.reset',
            default => 'filament.sezione.auth.password-reset.reset',
        };

        $url = url(route($panelRoute, [
            'token' => $this->token,
            'email' => $this->user->email,
        ]));

        $message = (new MailMessage)
            ->subject('Imposta la tua password — Prenotar CAI Lombardia')
            ->greeting("Ciao {$this->user->name},")
            ->line('Il tuo account per la piattaforma di prenotazione delle torri di arrampicata del **CAI GR Lombardia** è stato creato.')
            ->action('Imposta la tua password', $url)
            ->line('Il link è valido per **7 giorni**. Se non hai richiesto questo account, ignora questa email.')
            ->salutation('— Piattaforma Prenotar, Club Alpino Italiano GR Lombardia');

        if ($this->user->email_is_fallback) {
            $this->addCcForFallback($message);
        }

        return $message;
    }

    private function addCcForFallback(MailMessage $message): void
    {
        // CC: email sezione padre (se sottosezione e sezione ha email)
        $sottosezione = $this->user->sottosezione;
        if ($sottosezione !== null) {
            $sezioneEmail = $sottosezione->sezione?->email;
            if ($sezioneEmail !== null && filter_var($sezioneEmail, FILTER_VALIDATE_EMAIL)) {
                $message->cc($sezioneEmail);
            }
        }

        // CC: admin GR
        $settings = app(GrSettings::class);
        foreach ($settings->emails_notifiche_gr as $adminEmail) {
            if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $message->cc($adminEmail);
            }
        }
    }
}
