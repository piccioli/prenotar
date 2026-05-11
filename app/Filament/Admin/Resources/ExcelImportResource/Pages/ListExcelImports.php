<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ExcelImportResource\Pages;

use App\Filament\Admin\Resources\ExcelImportResource;
use Filament\Resources\Pages\ListRecords;

class ListExcelImports extends ListRecords
{
    protected static string $resource = ExcelImportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
