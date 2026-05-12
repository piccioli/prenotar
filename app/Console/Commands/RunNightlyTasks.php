<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ArchiveCompletedReservations;
use App\Jobs\SendReminderT10;
use App\Jobs\SendReminderT2gg;
use Illuminate\Console\Command;

class RunNightlyTasks extends Command
{
    protected $signature = 'prenotazioni:nightly';

    protected $description = 'Esegue i job notturni: archiviazione prenotazioni concluse e invio reminder.';

    public function handle(): int
    {
        ArchiveCompletedReservations::dispatch();
        SendReminderT10::dispatch();
        SendReminderT2gg::dispatch();

        $this->info('Job notturni accodati.');

        return self::SUCCESS;
    }
}
