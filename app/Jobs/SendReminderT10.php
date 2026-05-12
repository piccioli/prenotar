<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use App\Notifications\ReminderT10Notification;
use App\Settings\GrSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

final class SendReminderT10 implements ShouldQueue
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
        $soglia = $settings->giorni_minimi_caricamento_documenti;

        Prenotazione::query()
            ->with('user')
            ->where('status', PrenotazioneStatus::Approvata)
            ->whereNull('reminder_t10_inviato_at')
            ->whereDate('data_inizio_evento', '<=', today()->addDays($soglia))
            ->chunkById(50, function ($batch) use ($soglia): void {
                foreach ($batch as $p) {
                    if ($p->user === null) {
                        continue;
                    }
                    Notification::send($p->user, new ReminderT10Notification($p, $soglia));
                    $p->update(['reminder_t10_inviato_at' => now()]);
                }
            });
    }
}
