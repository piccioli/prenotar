<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Events\UserSetPasswordRequested;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\AuditLogger;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Utenti';

    protected static ?string $navigationGroup = 'Gestione accessi';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            TextInput::make('codice_cai')
                ->label('Codice CAI')
                ->unique(User::class, 'codice_cai', ignoreRecord: true)
                ->maxLength(20),
            Select::make('sezione_id')
                ->label('Sezione')
                ->relationship('sezione', 'nominativo')
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('sottosezione_id')
                ->label('Sottosezione')
                ->relationship('sottosezione', 'nominativo')
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('roles')
                ->label('Ruoli')
                ->multiple()
                ->relationship('roles', 'name')
                ->preload(),
            Toggle::make('is_active')
                ->label('Attivo')
                ->default(true),
            Toggle::make('email_is_fallback')
                ->label('Email fallback (auto-generata)')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->limit(35),
                TextColumn::make('codice_cai')
                    ->label('Codice CAI')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Ruolo')
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Attivo')
                    ->boolean(),
                IconColumn::make('email_is_fallback')
                    ->label('Email fallback')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),
                TextColumn::make('last_login_at')
                    ->label('Ultimo accesso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Ruolo')
                    ->relationship('roles', 'name')
                    ->multiple(),
                SelectFilter::make('sezione_id')
                    ->label('Sezione')
                    ->relationship('sezione', 'nominativo')
                    ->searchable(),
                Filter::make('is_active')
                    ->label('Solo attivi')
                    ->query(fn (Builder $query) => $query->where('is_active', true)),
                Filter::make('email_is_fallback')
                    ->label('Solo con email fallback')
                    ->query(fn (Builder $query) => $query->where('email_is_fallback', true)),
            ])
            ->actions([
                EditAction::make(),
                Impersonate::make()
                    ->visible(fn (User $record) => auth()->user()?->can('impersonate', $record) && $record->canBeImpersonated()),
                Action::make('reset_password')
                    ->label('Reset password')
                    ->icon('heroicon-o-key')
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record) => "Invierà un'email \"Imposta password\" a {$record->effective_contact_email}.")
                    ->visible(fn (User $record) => auth()->user()?->can('resetPassword', $record))
                    ->action(function (User $record): void {
                        event(new UserSetPasswordRequested($record));
                        app(AuditLogger::class)->logAdminAction('user.reset_password', $record, 'Reset password da admin');
                        Notification::make()->title('Email di reset password inviata.')->success()->send();
                    }),
                Action::make('toggle_active')
                    ->label(fn (User $record) => $record->is_active ? 'Disattiva' : 'Attiva')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('motivo')
                            ->label('Motivazione')
                            ->required()
                            ->minLength(5),
                    ])
                    ->visible(fn (User $record) => auth()->user()?->can('update', $record) && ! $record->isAdmin())
                    ->action(function (User $record, array $data): void {
                        $nuovoStato = ! $record->is_active;
                        $record->update(['is_active' => $nuovoStato]);
                        app(AuditLogger::class)->logAdminAction(
                            'user.toggle_active',
                            $record,
                            $data['motivo'],
                            ['is_active' => $nuovoStato],
                        );
                        $msg = $nuovoStato ? 'Utente attivato.' : 'Utente disattivato.';
                        Notification::make()->title($msg)->success()->send();
                    }),
            ])
            ->bulkActions([BulkActionGroup::make([])])
            ->defaultSort('name')
            ->searchPlaceholder('Cerca per nome, email, codice CAI');
    }

    /** @return Builder<User> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('prenotazioni');
    }

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
