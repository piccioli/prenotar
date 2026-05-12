<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AuditLogResource\Pages;

use App\Filament\Admin\Resources\AuditLogResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
            TextEntry::make('log_name')->label('Log')->badge(),
            TextEntry::make('event')->label('Evento'),
            TextEntry::make('causer.name')->label('Eseguito da')->default('—'),
            TextEntry::make('subject_type')
                ->label('Entità')
                ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
            TextEntry::make('subject_id')->label('ID entità')->default('—'),
            TextEntry::make('description')->label('Descrizione'),
            KeyValueEntry::make('properties.attributes')
                ->label('Valori nuovi')
                ->visible(fn ($record) => ! empty($record->properties['attributes'])),
            KeyValueEntry::make('properties.old')
                ->label('Valori precedenti')
                ->visible(fn ($record) => ! empty($record->properties['old'])),
            KeyValueEntry::make('properties')
                ->label('Proprietà aggiuntive')
                ->visible(fn ($record) => ! empty($record->properties)
                    && empty($record->properties['attributes'])
                    && empty($record->properties['old'])),
        ]);
    }
}
