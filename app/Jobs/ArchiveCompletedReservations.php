<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PrenotazioneStatus;
use App\Models\Prenotazione;
use App\Services\PrenotazioneStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ArchiveCompletedReservations implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct()
    {
        $this->onQueue('archive');
    }

    public function handle(PrenotazioneStateMachine $sm): void
    {
        Prenotazione::query()
            ->where('status', PrenotazioneStatus::InviatoAssicurazione)
            ->whereDate('data_fine_evento', '<', today())
            ->chunkById(50, function ($batch) use ($sm): void {
                foreach ($batch as $p) {
                    try {
                        $sm->concludi($p);
                    } catch (\Throwable $e) {
                        Log::warning("ArchiveCompletedReservations: concludi auto fallito #{$p->id}: {$e->getMessage()}");
                    }
                }
            });
    }
}
