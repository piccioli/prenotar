<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Import\ExcelImportService;
use App\Services\Import\Exceptions\ImportAlreadyDoneException;
use Illuminate\Console\Command;

class SyncExcelCommand extends Command
{
    protected $signature = 'sync:excel
                            {path : Path assoluto o relativo al file xlsx}
                            {--force : Re-run anche se il file è già stato importato}';

    protected $description = 'Importa sezioni, sottosezioni e utenti dal file Excel CAI';

    public function handle(ExcelImportService $service): int
    {
        $path = (string) $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File non trovato: {$path}");

            return self::FAILURE;
        }

        try {
            $result = $service->import(
                absolutePath: $path,
                importedById: null,
                force: (bool) $this->option('force'),
            );
        } catch (ImportAlreadyDoneException $e) {
            $this->warn($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Importate', 'Aggiornate', 'In errore', 'User creati', 'User disattivati'],
            [[
                $result->righeImportate,
                $result->righeAggiornate,
                $result->righeInErrore,
                $result->userCreati,
                $result->userDisattivati,
            ]]
        );

        if ($result->log !== []) {
            $this->warn('Errori / avvisi:');
            foreach ($result->log as $entry) {
                $this->line("  - {$entry}");
            }
        }

        if ($result->righeInErrore > 0) {
            return self::FAILURE;
        }

        $this->info('Import completato con successo.');

        return self::SUCCESS;
    }
}
