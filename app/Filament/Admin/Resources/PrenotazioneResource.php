<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\PrenotazioneStatus;
use App\Filament\Admin\Resources\PrenotazioneResource\Pages;
use App\Models\Prenotazione;
use App\Models\PrenotazioneHistory;
use App\Services\AuditLogger;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PrenotazioneResource extends Resource
{
    protected static ?string $model = Prenotazione::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Prenotazioni';

    protected static ?string $navigationGroup = 'Gestione';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
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
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('nome_evento')
                    ->label('Evento')
                    ->searchable()
                    ->limit(35),
                TextColumn::make('user.name')
                    ->label('Richiedente')
                    ->limit(25),
                TextColumn::make('torre.nome')
                    ->label('Torre')
                    ->default('—'),
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (PrenotazioneStatus $state) => $state->label())
                    ->color(fn (PrenotazioneStatus $state) => $state->color()),
                TextColumn::make('data_inizio_prenotazione')
                    ->label('Dal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_fine_prenotazione')
                    ->label('Al')
                    ->date('d/m/Y'),
                TextColumn::make('created_at')
                    ->label('Creata il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(PrenotazioneStatus::class),
                SelectFilter::make('torre_id')
                    ->label('Torre')
                    ->relationship('torre', 'nome'),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('force_state')
                    ->label('Force state')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Bypass state machine — azione straordinaria')
                    ->modalDescription('Questa azione bypassa la state machine. Inserisci una motivazione dettagliata obbligatoria.')
                    ->form([
                        Select::make('status')
                            ->label('Nuovo stato')
                            ->options(PrenotazioneStatus::class)
                            ->required(),
                        Textarea::make('motivo')
                            ->label('Motivazione (obbligatoria)')
                            ->required()
                            ->minLength(10),
                    ])
                    ->visible(fn (Prenotazione $record) => auth()->user()?->can('forceState', $record))
                    ->action(function (Prenotazione $record, array $data): void {
                        $oldStatus = $record->status;
                        $newStatus = PrenotazioneStatus::from($data['status']);

                        DB::transaction(function () use ($record, $data, $oldStatus, $newStatus): void {
                            $record->update(['status' => $newStatus]);

                            PrenotazioneHistory::create([
                                'prenotazione_id' => $record->id,
                                'user_id' => auth()->id(),
                                'status_from' => $oldStatus,
                                'status_to' => $newStatus,
                                'note' => '[FORCE STATE] '.$data['motivo'],
                                'created_at' => now(),
                            ]);
                        });

                        app(AuditLogger::class)->logAdminAction(
                            'prenotazione.force_state',
                            $record->fresh(),
                            $data['motivo'],
                            ['from' => $oldStatus->value, 'to' => $newStatus->value],
                        );

                        Notification::make()
                            ->title("Stato forzato: {$oldStatus->label()} → {$newStatus->label()}")
                            ->success()
                            ->send();
                    }),
                Action::make('hard_delete')
                    ->label('Hard delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminazione definitiva — azione irreversibile')
                    ->modalDescription('Il record verrà eliminato permanentemente. Lo snapshot sarà conservato nell\'audit log.')
                    ->form([
                        Textarea::make('motivo')
                            ->label('Motivazione (obbligatoria)')
                            ->required()
                            ->minLength(10),
                    ])
                    ->visible(fn (Prenotazione $record) => auth()->user()?->can('hardDelete', $record))
                    ->action(function (Prenotazione $record, array $data): void {
                        app(AuditLogger::class)->logAdminAction(
                            'prenotazione.hard_delete',
                            $record,
                            $data['motivo'],
                            [
                                'snapshot' => [
                                    'id' => $record->id,
                                    'nome_evento' => $record->nome_evento,
                                    'status' => $record->status->value,
                                    'user_id' => $record->user_id,
                                    'torre_id' => $record->torre_id,
                                    'data_inizio_prenotazione' => $record->data_inizio_prenotazione->toDateString(),
                                    'data_fine_prenotazione' => $record->data_fine_prenotazione->toDateString(),
                                ],
                            ],
                        );

                        $record->forceDelete();

                        Notification::make()
                            ->title('Prenotazione eliminata definitivamente.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([BulkActionGroup::make([])])
            ->defaultSort('created_at', 'desc');
    }

    /** @return Builder<Prenotazione> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrenotazioni::route('/'),
            'view' => Pages\ViewPrenotazione::route('/{record}'),
        ];
    }
}
