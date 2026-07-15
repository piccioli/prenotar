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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Utenti';

    protected static ?string $navigationGroup = 'Gestione';

    protected static ?int $navigationSort = 3;

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
            ->recordUrl(fn (User $record): string => Pages\EditUser::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->limit(35),
                TextColumn::make('ruolo')
                    ->label('Ruolo')
                    ->badge()
                    ->getStateUsing(fn (User $record): string => self::ruoloLabel($record))
                    ->color(fn (string $state): string => self::ruoloColor($state)),
                TextColumn::make('appartenenza')
                    ->label('Appartenenza')
                    ->html()
                    ->getStateUsing(fn (User $record): string => self::appartenenzaLabel($record)),
                TextColumn::make('is_active')
                    ->label('Stato')
                    ->html()
                    ->sortable()
                    ->getStateUsing(fn (User $record): string => self::statoLabel($record)),
                TextColumn::make('codice_cai')
                    ->label('Codice CAI')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('email_is_fallback')
                    ->label('Email fallback')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')
                    ->label('Ultimo accesso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Impersonate::make()
                    ->label('Impersona')
                    ->button()
                    ->outlined()
                    ->color('primary')
                    ->visible(fn (User $record) => auth()->user()?->can('impersonate', $record) && $record->canBeImpersonated()),
                Action::make('reset_password')
                    ->label('Reset password')
                    ->icon('heroicon-o-key')
                    ->iconButton()
                    ->color('gray')
                    ->tooltip('Reset password')
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
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-user-minus' : 'heroicon-o-user-plus')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->iconButton()
                    ->tooltip(fn (User $record) => $record->is_active ? 'Disattiva' : 'Attiva')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => $record->is_active ? 'Disattiva utente' : 'Attiva utente')
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

    public static function ruoloLabel(User $record): string
    {
        return match (true) {
            $record->isAdmin() => 'Admin',
            $record->isGrManager() => 'GR Manager',
            $record->sottosezione_id !== null => 'Sottosezione',
            default => 'Sezione',
        };
    }

    public static function ruoloColor(string $label): string
    {
        return match ($label) {
            'Admin' => 'warning',
            'GR Manager' => 'success',
            default => 'info',
        };
    }

    public static function appartenenzaLabel(User $record): string
    {
        if ($record->sottosezione !== null) {
            return view('filament.components.etichetta-sezione', ['sottosezione' => $record->sottosezione])->render();
        }

        if ($record->sezione !== null) {
            return view('filament.components.etichetta-sezione', ['sezione' => $record->sezione])->render();
        }

        if ($record->isGrManager()) {
            return 'GR Lombardia';
        }

        return '—';
    }

    public static function statoLabel(User $record): string
    {
        return view('filament.admin.components.stato-utente', [
            'attivo' => $record->is_active,
            'motivo' => $record->is_active ? null : self::ultimoMotivoDisattivazione($record),
        ])->render();
    }

    private static function ultimoMotivoDisattivazione(User $record): ?string
    {
        $motivo = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $record->getKey())
            ->where('event', 'user.toggle_active')
            ->latest()
            ->first()
            ?->properties
            ->get('motivo');

        return is_string($motivo) ? $motivo : null;
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
