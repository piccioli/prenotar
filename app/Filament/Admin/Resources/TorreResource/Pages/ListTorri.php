<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TorreResource\Pages;

use App\Filament\Admin\Resources\TorreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTorri extends ListRecords
{
    protected static string $resource = TorreResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
