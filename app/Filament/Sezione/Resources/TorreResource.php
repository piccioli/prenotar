<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources;

use App\Filament\Sezione\Resources\TorreResource\Pages;
use App\Models\Torre;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TorreResource extends Resource
{
    protected static ?string $model = Torre::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Carte d\'identità torri';

    protected static ?string $modelLabel = 'Torre';

    protected static ?string $pluralModelLabel = 'Torri';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Dati generali')
                ->schema([
                    TextEntry::make('nome')
                        ->label('Nome'),
                    TextEntry::make('indirizzo_deposito')
                        ->label('Indirizzo deposito'),
                    TextEntry::make('descrizione')
                        ->label('Descrizione')
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Documenti')
                ->schema([
                    TextEntry::make('foto_path')
                        ->label('Foto')
                        ->formatStateUsing(fn (?string $state) => $state ? basename($state) : '—')
                        ->url(fn (?string $state) => $state ? asset('storage/'.$state) : null)
                        ->openUrlInNewTab(),
                    TextEntry::make('specs_tecniche_pdf_path')
                        ->label('Specifiche tecniche (PDF)')
                        ->formatStateUsing(fn (?string $state) => $state ? basename($state) : '—')
                        ->url(fn (?string $state) => $state ? asset('storage/'.$state) : null)
                        ->openUrlInNewTab(),
                    TextEntry::make('manuale_pdf_path')
                        ->label('Manuale di montaggio (PDF)')
                        ->formatStateUsing(fn (?string $state) => $state ? basename($state) : '—')
                        ->url(fn (?string $state) => $state ? asset('storage/'.$state) : null)
                        ->openUrlInNewTab(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('indirizzo_deposito')
                    ->label('Indirizzo deposito')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Attiva')
                    ->boolean(),
            ])
            ->defaultSort('id', 'asc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTorri::route('/'),
            'view' => Pages\ViewTorre::route('/{record}'),
        ];
    }

    /** @return Builder<Torre> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_active', true);
    }
}
