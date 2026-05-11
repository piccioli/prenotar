<?php

declare(strict_types=1);

namespace App\Services\Import\Dto;

use App\Models\ExcelImport;

final readonly class ImportResult
{
    public function __construct(
        public int $righeImportate,
        public int $righeAggiornate,
        public int $righeInErrore,
        public int $userCreati,
        public int $userDisattivati,
        /** @var string[] */
        public array $log,
        public ExcelImport $record,
    ) {}
}
