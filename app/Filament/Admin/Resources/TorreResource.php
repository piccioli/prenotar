<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TorreResource\Pages;
use App\Models\Torre;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TorreResource extends Resource
{
    protected static ?string $model = Torre::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Torri';

    protected static ?string $navigationGroup = 'Anagrafiche';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nome')
                ->label('Nome torre')
                ->required()
                ->maxLength(100),
            Textarea::make('descrizione')
                ->label('Descrizione')
                ->rows(3),
            TextInput::make('indirizzo_deposito')
                ->label('Indirizzo deposito')
                ->required()
                ->maxLength(255),
            Textarea::make('specs_tecniche')
                ->label('Specifiche tecniche')
                ->rows(4),
            FileUpload::make('foto_path')
                ->label('Foto torre')
                ->disk('local')
                ->directory('torri/foto')
                ->visibility('private')
                ->image()
                ->nullable(),
            FileUpload::make('manuale_pdf_path')
                ->label('Manuale PDF')
                ->disk('local')
                ->directory('torri/manuali')
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf'])
                ->nullable(),
            FileUpload::make('specs_tecniche_pdf_path')
                ->label('Scheda tecnica PDF')
                ->disk('local')
                ->directory('torri/schede')
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf'])
                ->nullable(),
            Toggle::make('is_active')
                ->label('Torre attiva')
                ->default(true),
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
                    ->label('Deposito')
                    ->limit(40),
                IconColumn::make('is_active')
                    ->label('Attiva')
                    ->boolean(),
                TextColumn::make('prenotazioni_count')
                    ->label('Prenotazioni attive')
                    ->counts('prenotazioni')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('toggle_active')
                    ->label(fn (Torre $record) => $record->is_active ? 'Disattiva' : 'Attiva')
                    ->icon(fn (Torre $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Torre $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->visible(fn (Torre $record) => auth()->user()?->can('update', $record))
                    ->action(function (Torre $record): void {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()->title('Stato torre aggiornato.')->success()->send();
                    }),
            ])
            ->bulkActions([BulkActionGroup::make([])])
            ->defaultSort('nome');
    }

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTorri::route('/'),
            'create' => Pages\CreateTorre::route('/create'),
            'edit' => Pages\EditTorre::route('/{record}/edit'),
        ];
    }
}
