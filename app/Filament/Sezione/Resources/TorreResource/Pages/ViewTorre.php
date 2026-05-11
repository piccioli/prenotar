<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources\TorreResource\Pages;

use App\Filament\Sezione\Resources\TorreResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTorre extends ViewRecord
{
    protected static string $resource = TorreResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
