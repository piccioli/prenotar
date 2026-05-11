<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources\TorreResource\Pages;

use App\Filament\Sezione\Resources\TorreResource;
use Filament\Resources\Pages\ListRecords;

class ListTorri extends ListRecords
{
    protected static string $resource = TorreResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
