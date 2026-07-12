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
use Filament\Support\Colors\Color;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
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
            ->searchPlaceholder('Cerca sezione o evento…')
            ->columns([
                TextColumn::make('proprietario_label')
                    ->label('Richiedente')
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search): void {
                            $q->whereHas('sezione', fn (Builder $sq) => $sq->where('nominativo', 'like', "%{$search}%"))
                                ->orWhereHas('sottosezione', fn (Builder $sq) => $sq->where('nominativo', 'like', "%{$search}%"));
                        });
                    })
                    ->getStateUsing(fn (Prenotazione $record): string => self::richiedenteLabel($record)),

                TextColumn::make('nome_evento')
                    ->label('Evento')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('periodo')
                    ->label('Periodo')
                    ->getStateUsing(fn (Prenotazione $record): string => self::periodoLabel($record))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('data_inizio_prenotazione', $direction)),

                TextColumn::make('torre.nome')
                    ->label('Torre')
                    ->badge()
                    ->color(fn (Prenotazione $record): array => Color::hex(Torre::coloreHexPer($record->torre)))
                    ->default('—'),

                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (PrenotazioneStatus $state): string => $state->label())
                    ->color(fn (PrenotazioneStatus $state): string => $state->color()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->multiple()
                    ->options(collect(PrenotazioneStatus::cases())->mapWithKeys(
                        fn (PrenotazioneStatus $s) => [$s->value => $s->label()]
                    )),

                SelectFilter::make('torre_id')
                    ->label('Torre')
                    ->options(Torre::where('is_active', true)->pluck('nome', 'id')),

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
                Tables\Actions\ViewAction::make()
                    ->label('Vedi')
                    ->icon('heroicon-o-arrow-right')
                    ->iconPosition(IconPosition::After),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Nessuna prenotazione')
            ->emptyStateDescription('Non ci sono prenotazioni che corrispondono ai criteri di ricerca.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    private static function richiedenteLabel(Prenotazione $record): string
    {
        if ($record->sottosezione !== null) {
            return view('filament.components.etichetta-sezione', ['sottosezione' => $record->sottosezione])->render();
        }

        return 'Sezione di '.view('filament.components.etichetta-sezione', ['sezione' => $record->sezione])->render();
    }

    private static function periodoLabel(Prenotazione $record): string
    {
        $inizio = $record->data_inizio_prenotazione;
        $fine = $record->data_fine_prenotazione;

        return $inizio->isSameDay($fine)
            ? $inizio->translatedFormat('d M Y')
            : $inizio->translatedFormat('d M').' – '.$fine->translatedFormat('d M Y');
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
