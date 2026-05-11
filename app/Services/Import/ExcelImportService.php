<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\ExcelImport;
use App\Models\Sezione;
use App\Models\Sottosezione;
use App\Models\User;
use App\Services\Import\Dto\ImportResult;
use App\Services\Import\Exceptions\ImportAlreadyDoneException;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;

final class ExcelImportService
{
    public function import(string $absolutePath, ?int $importedById = null, bool $force = false): ImportResult
    {
        $hash = md5_file($absolutePath);

        if (! $force && ExcelImport::where('hash', $hash)->exists()) {
            throw new ImportAlreadyDoneException((string) $hash);
        }

        // Snapshot dei codici presenti prima dell'import per il soft-deactivate
        $codiciSezioniPre = Sezione::pluck('codice')->all();
        $codiciSottosezioniPre = Sottosezione::pluck('codice')->all();

        $sezioniImport = new SezioniSheetImport;
        $sottosezioniImport = new SottosezioniSheetImport;

        $mainImport = new class($sezioniImport, $sottosezioniImport) implements WithMultipleSheets
        {
            public function __construct(
                private readonly SezioniSheetImport $sezioni,
                private readonly SottosezioniSheetImport $sottosezioni,
            ) {}

            /** @return array<int, SezioniSheetImport|SottosezioniSheetImport> */
            public function sheets(): array
            {
                return [
                    0 => $this->sezioni,
                    1 => $this->sottosezioni,
                ];
            }
        };

        Excel::import($mainImport, $absolutePath);

        // Soft-deactivate: codici non più presenti nel file
        $disattivati = 0;
        $codiciSezioniRimossi = array_diff($codiciSezioniPre, $sezioniImport->codiciProcessati);
        if ($codiciSezioniRimossi !== []) {
            Sezione::whereIn('codice', $codiciSezioniRimossi)->update(['is_active' => false]);
            User::whereIn('codice_cai', $codiciSezioniRimossi)->update(['is_active' => false]);
            $disattivati += count($codiciSezioniRimossi);
        }

        $codiciSottosezioniRimossi = array_diff($codiciSottosezioniPre, $sottosezioniImport->codiciProcessati);
        if ($codiciSottosezioniRimossi !== []) {
            Sottosezione::whereIn('codice', $codiciSottosezioniRimossi)->update(['is_active' => false]);
            User::whereIn('codice_cai', $codiciSottosezioniRimossi)->update(['is_active' => false]);
            $disattivati += count($codiciSottosezioniRimossi);
        }

        $righeImportate = $sezioniImport->importate + $sottosezioniImport->importate;
        $righeAggiornate = $sezioniImport->aggiornate + $sottosezioniImport->aggiornate;
        $righeInErrore = $sezioniImport->inErrore + $sottosezioniImport->inErrore;
        $userCreati = $sezioniImport->userCreati + $sottosezioniImport->userCreati;
        $log = array_merge($sezioniImport->log, $sottosezioniImport->log);

        $record = ExcelImport::create([
            'filename' => basename($absolutePath),
            'hash' => $hash,
            'imported_by' => $importedById,
            'righe_importate' => $righeImportate,
            'righe_aggiornate' => $righeAggiornate,
            'righe_in_errore' => $righeInErrore,
            'log' => $log,
        ]);

        return new ImportResult(
            righeImportate: $righeImportate,
            righeAggiornate: $righeAggiornate,
            righeInErrore: $righeInErrore,
            userCreati: $userCreati,
            userDisattivati: $disattivati,
            log: $log,
            record: $record,
        );
    }
}
