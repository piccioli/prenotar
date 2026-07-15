<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ExcelImportResource\Pages;
use App\Models\ExcelImport;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExcelImportResource extends Resource
{
    protected static ?string $model = ExcelImport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Import Excel';

    protected static ?string $navigationGroup = 'Gestione';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->weight(FontWeight::Bold)
                    ->sortable(),
                TextColumn::make('righe_totali')
                    ->label('Righe totali')
                    ->state(fn (ExcelImport $record): int => $record->righe_importate + $record->righe_aggiornate + $record->righe_in_errore),
                TextColumn::make('righe_importate')
                    ->label('Importate')
                    ->numeric(),
                TextColumn::make('righe_aggiornate')
                    ->label('Aggiornate')
                    ->numeric(),
                TextColumn::make('righe_in_errore')
                    ->label('In errore')
                    ->numeric()
                    ->weight(FontWeight::Bold)
                    ->badge(fn (int $state): bool => $state > 0)
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('filename')
                    ->label('File')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('importedBy.name')
                    ->label('Da')
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dettaglio')
                    ->label('')
                    ->state('Dettaglio')
                    ->icon('heroicon-o-arrow-right')
                    ->iconPosition(IconPosition::After)
                    ->weight(FontWeight::Bold)
                    ->color('primary')
                    ->alignEnd(),
            ])
            ->recordUrl(fn (ExcelImport $record): string => Pages\ViewExcelImport::getUrl(['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExcelImports::route('/'),
            'view' => Pages\ViewExcelImport::route('/{record}'),
        ];
    }
}
