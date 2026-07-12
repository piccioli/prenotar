<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AuditLogResource\Pages;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class AuditLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Audit log';

    protected static ?string $navigationGroup = 'Diagnostica';

    protected static ?int $navigationSort = 3;

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d M, H:i')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Chi')
                    ->weight('bold')
                    ->description(fn (Activity $record) => $record->causer instanceof User ? $record->causer->email : null)
                    ->searchable(['name', 'email'])
                    ->default('Sistema'),
                TextColumn::make('description')
                    ->label('Cosa')
                    ->getStateUsing(fn (Activity $record) => self::descrizioneEvento($record))
                    ->wrap()
                    ->limit(80),
                TextColumn::make('event')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state) => self::etichettaEvento($state))
                    ->badge(fn (string $state) => self::coloreEvento($state) !== 'gray')
                    ->color(fn (string $state) => self::coloreEvento($state))
                    ->weight('bold'),
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject_type')
                    ->label('Entità')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject_id')
                    ->label('ID entità')
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Tipo evento')
                    ->options(self::eventoOptions()),
                Filter::make('date_range')
                    ->label('Periodo')
                    ->form([
                        DatePicker::make('from')->label('Dal'),
                        DatePicker::make('to')->label('Al'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['to'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
                    }),
                SelectFilter::make('log_name')
                    ->label('Log')
                    ->options([
                        'default' => 'default',
                        'admin' => 'admin',
                        'user' => 'user',
                        'prenotazione' => 'prenotazione',
                        'torre' => 'torre',
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    /** @return array<string, string> */
    private static function eventoOptions(): array
    {
        return [
            'created' => 'Creazione',
            'updated' => 'Modifica',
            'deleted' => 'Eliminazione',
            'prenotazione.force_state' => 'Stato forzato',
            'prenotazione.hard_delete' => 'Eliminazione definitiva',
            'user.impersonate_begin' => 'Impersonate avviato',
            'user.impersonate_end' => 'Impersonate terminato',
            'user.reset_password' => 'Reset password',
            'user.toggle_active' => 'Attivazione/disattivazione utente',
            'email.test' => 'Test email',
        ];
    }

    private static function etichettaEvento(string $event): string
    {
        return self::eventoOptions()[$event] ?? $event;
    }

    private static function coloreEvento(string $event): string
    {
        return match (true) {
            str_contains($event, 'hard_delete'), $event === 'deleted' => 'danger',
            str_contains($event, 'impersonate') => 'warning',
            default => 'gray',
        };
    }

    private static function descrizioneEvento(Activity $record): string
    {
        $motivo = $record->properties['motivo'] ?? null;

        if (is_string($motivo) && $motivo !== '') {
            return $motivo;
        }

        $entita = $record->subject_type ? self::entitaLabel($record->subject_type) : null;

        if (! $entita) {
            return $record->description;
        }

        $participio = match ($record->event) {
            'created' => "creat{$entita['genere']}",
            'updated' => "aggiornat{$entita['genere']}",
            'deleted' => "eliminat{$entita['genere']}",
            default => $record->description,
        };

        return sprintf('%s #%s %s', $entita['label'], $record->subject_id ?? '—', $participio);
    }

    /** @return array{label: string, genere: string} */
    private static function entitaLabel(string $subjectType): array
    {
        return match (class_basename($subjectType)) {
            'Torre' => ['label' => 'Torre', 'genere' => 'a'],
            'Prenotazione' => ['label' => 'Prenotazione', 'genere' => 'a'],
            'User' => ['label' => 'Utente', 'genere' => 'o'],
            default => ['label' => class_basename($subjectType), 'genere' => 'o'],
        };
    }

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
