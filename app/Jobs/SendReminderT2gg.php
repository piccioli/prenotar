<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use App\Notifications\ReminderT2ggNotification;
use App\Settings\GrSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

final class SendReminderT2gg implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct()
    {
        $this->onQueue('archive');
    }

    public function handle(GrSettings $settings): void
    {
        $sogliaGiorni = (int) ceil($settings->ore_minime_richiesta_assicurazione / 24);
        $emailsGr = $settings->emails_notifiche_gr;

        if (empty($emailsGr)) {
            return;
        }

        Prenotazione::query()
            ->where('status', PrenotazioneStatus::InviatoPdfFirmato)
            ->whereNull('reminder_t2gg_inviato_at')
            ->whereNotNull('data_ritiro')
            ->whereDate('data_ritiro', '<=', today()->addDays($sogliaGiorni))
            ->chunkById(50, function ($batch) use ($emailsGr): void {
                foreach ($batch as $p) {
                    Notification::route('mail', $emailsGr)
                        ->notify(new ReminderT2ggNotification($p));
                    $p->update(['reminder_t2gg_inviato_at' => now()]);
                }
            });
    }
}
