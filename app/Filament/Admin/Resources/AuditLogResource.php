<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AuditLogResource\Pages;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
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

    protected static ?string $navigationGroup = 'Audit';

    protected static ?int $navigationSort = 1;

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
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge(),
                TextColumn::make('event')
                    ->label('Evento')
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label('Eseguito da')
                    ->default('—'),
                TextColumn::make('subject_type')
                    ->label('Entità')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                TextColumn::make('subject_id')
                    ->label('ID entità')
                    ->default('—'),
                TextColumn::make('description')
                    ->label('Descrizione')
                    ->limit(50),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Log')
                    ->options([
                        'default' => 'default',
                        'admin' => 'admin',
                        'user' => 'user',
                        'prenotazione' => 'prenotazione',
                        'torre' => 'torre',
                    ]),
                SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'created' => 'created',
                        'updated' => 'updated',
                        'deleted' => 'deleted',
                        'prenotazione.force_state' => 'force_state',
                        'prenotazione.hard_delete' => 'hard_delete',
                        'user.impersonate_begin' => 'impersonate_begin',
                        'user.impersonate_end' => 'impersonate_end',
                        'user.reset_password' => 'reset_password',
                        'user.toggle_active' => 'toggle_active',
                        'email.test' => 'email_test',
                    ]),
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
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
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
