<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PrenotazioneResource\Pages;

use App\Filament\Admin\Resources\PrenotazioneResource;
use Filament\Resources\Pages\ListRecords;

class ListPrenotazioni extends ListRecords
{
    protected static string $resource = PrenotazioneResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
