<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ExcelImportResource\Pages;

use App\Filament\Admin\Resources\ExcelImportResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewExcelImport extends ViewRecord
{
    protected static string $resource = ExcelImportResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Dettagli import')
                    ->schema([
                        TextEntry::make('filename')->label('File'),
                        TextEntry::make('hash')->label('MD5'),
                        TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
                        TextEntry::make('importedBy.name')->label('Importato da')->default('—'),
                        TextEntry::make('righe_importate')->label('Righe importate'),
                        TextEntry::make('righe_aggiornate')->label('Righe aggiornate'),
                        TextEntry::make('righe_in_errore')->label('Righe in errore'),
                    ])
                    ->columns(2),
                Section::make('Log errori / avvisi')
                    ->schema([
                        RepeatableEntry::make('log')
                            ->label('')
                            ->schema([
                                TextEntry::make('')->formatStateUsing(fn ($state) => $state),
                            ])
                            ->contained(false),
                    ])
                    ->visible(fn ($record) => ! empty($record->log)),
            ]);
    }
}
