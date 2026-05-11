<?php

declare(strict_types=1);

namespace App\Filament\Gr\Resources;

use App\Enums\PrenotazioneStatus;
use App\Filament\Gr\Resources\PrenotazioneResource\Pages;
use App\Models\Prenotazione;
use App\Models\Sezione;
use App\Models\Sottosezione;
use App\Models\Torre;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PrenotazioneResource extends Resource
{
    protected static ?string $model = Prenotazione::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Prenotazioni';

    protected static ?string $modelLabel = 'Prenotazione';

    protected static ?string $pluralModelLabel = 'Prenotazioni';

    protected static ?int $navigationSort = 10;

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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_inizio_prenotazione', 'desc')
            ->columns([
                TextColumn::make('proprietario_label')
                    ->label('Sezione / Sez.')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->whereHas('sezione', fn (Builder $sq) => $sq->where('nominativo', 'like', "%{$search}%"))
                                ->orWhereHas('sottosezione', fn (Builder $sq) => $sq->where('nominativo', 'like', "%{$search}%"));
                        });
                    })
                    ->getStateUsing(fn (Prenotazione $record): string => $record->proprietario_label),

                TextColumn::make('nome_evento')
                    ->label('Evento')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('torre.nome')
                    ->label('Torre')
                    ->badge()
                    ->color(fn (mixed $state, Prenotazione $record): string => match ($record->torre_id) {
                        1 => 'info',
                        2 => 'warning',
                        default => 'gray',
                    })
                    ->default('—'),

                TextColumn::make('data_inizio_prenotazione')
                    ->label('Da')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('data_fine_prenotazione')
                    ->label('A')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (PrenotazioneStatus $state): string => $state->label())
                    ->color(fn (PrenotazioneStatus $state): string => $state->color()),

                IconColumn::make('has_delibera')
                    ->label('Delibera')
                    ->boolean()
                    ->getStateUsing(fn (Prenotazione $record): bool => $record->hasMedia('delibera_consiglio')),
            ])
            ->filters([
                SelectFilter::make('torre_id')
                    ->label('Torre')
                    ->options(Torre::where('is_active', true)->pluck('nome', 'id')),

                SelectFilter::make('status')
                    ->label('Stato')
                    ->multiple()
                    ->options(collect(PrenotazioneStatus::cases())->mapWithKeys(
                        fn (PrenotazioneStatus $s) => [$s->value => $s->label()]
                    )),

                SelectFilter::make('sezione_id')
                    ->label('Sezione')
                    ->searchable()
                    ->options(Sezione::orderBy('nominativo')->pluck('nominativo', 'id')),

                SelectFilter::make('sottosezione_id')
                    ->label('Sottosezione')
                    ->searchable()
                    ->options(Sottosezione::orderBy('nominativo')->pluck('nominativo', 'id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrenotazioni::route('/'),
            'view' => Pages\ViewPrenotazione::route('/{record}'),
        ];
    }

    /** @return Builder<Prenotazione> */
    public static function getEloquentQuery(): Builder
    {
        return Prenotazione::query()->orderBy('data_inizio_prenotazione', 'desc');
    }
}
