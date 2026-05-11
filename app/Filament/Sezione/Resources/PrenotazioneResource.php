<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources;

use App\Enums\PrenotazioneStatus;
use App\Enums\ResponsabileTipo;
use App\Filament\Sezione\Resources\PrenotazioneResource\Pages;
use App\Filament\Sezione\Widgets\CalendarioPrenotazioniWidget;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Rules\NoOverlapTorre;
use App\Rules\UnicaPrenotazioneAttivaPerUser;
use App\Services\PrenotazioneStateMachine;
use App\Settings\GrSettings;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrenotazioneResource extends Resource
{
    protected static ?string $model = Prenotazione::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Prenotazioni';

    protected static ?string $modelLabel = 'Prenotazione';

    protected static ?string $pluralModelLabel = 'Prenotazioni';

    protected static ?int $navigationSort = 10;

    /** @return Builder<Prenotazione> */
    public static function getEloquentQuery(): Builder
    {
        return Prenotazione::query()
            ->where('user_id', auth()->id())
            ->orderBy('data_inizio_prenotazione', 'desc');
    }

    /** @return list<Forms\Components\Wizard\Step> */
    public static function wizardSteps(): array
    {
        return [
            Forms\Components\Wizard\Step::make('Quando & dove')
                ->icon('heroicon-o-calendar')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\DatePicker::make('data_inizio_prenotazione')
                            ->label('Data inizio prenotazione')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(fn () => today()->addDays(app(GrSettings::class)->giorni_minimi_caricamento_documenti))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $livewire): void {
                                if ($state && $get('data_fine_prenotazione') && $get('data_fine_prenotazione') < $state) {
                                    $set('data_fine_prenotazione', $state);
                                }
                                $livewire->dispatch('preview-range-changed',
                                    inizio: $state,
                                    fine: $get('data_fine_prenotazione'),
                                );
                            })
                            ->rules([new UnicaPrenotazioneAttivaPerUser(auth()->user())])
                            ->helperText(fn () => 'Deve essere almeno '.app(GrSettings::class)->giorni_minimi_caricamento_documenti.' giorni da oggi.'),

                        Forms\Components\DatePicker::make('data_fine_prenotazione')
                            ->label('Data fine prenotazione')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (Forms\Get $get) => $get('data_inizio_prenotazione') ?? today())
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Get $get, $livewire): void {
                                $livewire->dispatch('preview-range-changed',
                                    inizio: $get('data_inizio_prenotazione'),
                                    fine: $state,
                                );
                            }),

                        Forms\Components\Select::make('torre_id')
                            ->label('Torre (opzionale)')
                            ->options(Torre::where('is_active', true)->pluck('nome', 'id'))
                            ->placeholder('Nessuna preferenza — il GR assegnerà la torre disponibile')
                            ->live()
                            ->rules(fn (Forms\Get $get): array => [
                                new NoOverlapTorre(
                                    torreId: $get('torre_id') ? (int) $get('torre_id') : null,
                                    dataInizio: (string) ($get('data_inizio_prenotazione') ?? ''),
                                    dataFine: (string) ($get('data_fine_prenotazione') ?? ''),
                                ),
                            ])
                            ->helperText('Puoi lasciare vuoto. Il GR assegnerà la torre in fase di approvazione.'),
                    ]),

                    Forms\Components\Livewire::make(CalendarioPrenotazioniWidget::class)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Wizard\Step::make('Evento')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Forms\Components\TextInput::make('nome_evento')
                        ->label('Nome evento')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('tipo_evento')
                        ->label('Tipo evento')
                        ->required()
                        ->options([
                            'fiera' => 'Fiera',
                            'manifestazione_cai' => 'Manifestazione CAI',
                            'evento_promozionale' => 'Evento promozionale',
                            'corso' => 'Corso',
                            'altro' => 'Altro',
                        ]),

                    Forms\Components\Textarea::make('descrizione_evento')
                        ->label('Descrizione')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('indirizzo_evento')
                        ->label('Indirizzo evento')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DatePicker::make('data_inizio_evento')
                            ->label('Data inizio evento')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (Forms\Get $get) => $get('data_inizio_prenotazione')),

                        Forms\Components\DatePicker::make('data_fine_evento')
                            ->label('Data fine evento')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (Forms\Get $get) => $get('data_inizio_evento')),
                    ]),
                ]),

            Forms\Components\Wizard\Step::make('Logistica trasporto')
                ->icon('heroicon-o-truck')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DatePicker::make('data_ritiro')
                            ->label('Data ritiro torre')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('luogo_ritiro')
                            ->label('Luogo ritiro')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('data_riconsegna')
                            ->label('Data riconsegna torre')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('luogo_riconsegna')
                            ->label('Luogo riconsegna')
                            ->maxLength(255),
                    ]),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('azienda_trasporto')
                            ->label('Azienda di trasporto')
                            ->default('Montagna Servizi')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('targa_autoveicolo')
                            ->label('Targa autoveicolo')
                            ->maxLength(20),
                    ]),
                ]),

            Forms\Components\Wizard\Step::make('Responsabile in loco')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('responsabile_nome')
                            ->label('Nome e cognome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('responsabile_tipo')
                            ->label('Qualifica CAI')
                            ->required()
                            ->options(collect(ResponsabileTipo::cases())->mapWithKeys(
                                fn (ResponsabileTipo $t) => [$t->value => $t->label()]
                            )),
                    ]),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('responsabile_titolo_cai')
                            ->label('Titolo CAI')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('responsabile_codice_cai')
                            ->label('Codice CAI')
                            ->maxLength(50),
                    ]),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('responsabile_telefono')
                            ->label('Telefono')
                            ->required()
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('responsabile_email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->maxLength(255),
                    ]),
                ]),

            Forms\Components\Wizard\Step::make('Riepilogo')
                ->icon('heroicon-o-check-circle')
                ->schema([
                    Forms\Components\Placeholder::make('riepilogo_evento')
                        ->label('Evento')
                        ->content(fn (Forms\Get $get): string => implode(' — ', array_filter([
                            $get('nome_evento'),
                            $get('tipo_evento'),
                            $get('indirizzo_evento'),
                        ]))),

                    Forms\Components\Placeholder::make('riepilogo_periodo')
                        ->label('Periodo prenotazione torre')
                        ->content(fn (Forms\Get $get): string => implode(' → ', array_filter([
                            $get('data_inizio_prenotazione'),
                            $get('data_fine_prenotazione'),
                        ]))),

                    Forms\Components\Placeholder::make('riepilogo_responsabile')
                        ->label('Responsabile in loco')
                        ->content(fn (Forms\Get $get): string => implode(', ', array_filter([
                            $get('responsabile_nome'),
                            $get('responsabile_tipo'),
                            $get('responsabile_telefono'),
                        ]))),

                    Forms\Components\Placeholder::make('avviso_delibera')
                        ->label('')
                        ->content('Dopo aver salvato la bozza, carica la delibera del consiglio dalla pagina di modifica per poter inviare la richiesta al GR.'),
                ]),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Periodo & Torre')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\DatePicker::make('data_inizio_prenotazione')
                            ->label('Data inizio prenotazione')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('data_fine_prenotazione')
                            ->label('Data fine prenotazione')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Select::make('torre_id')
                            ->label('Torre')
                            ->options(Torre::where('is_active', true)->pluck('nome', 'id'))
                            ->placeholder('Nessuna preferenza'),
                    ]),
                ]),

            Forms\Components\Section::make('Evento')
                ->schema([
                    Forms\Components\TextInput::make('nome_evento')
                        ->label('Nome evento')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('tipo_evento')
                        ->label('Tipo evento')
                        ->required()
                        ->options([
                            'fiera' => 'Fiera',
                            'manifestazione_cai' => 'Manifestazione CAI',
                            'evento_promozionale' => 'Evento promozionale',
                            'corso' => 'Corso',
                            'altro' => 'Altro',
                        ]),

                    Forms\Components\Textarea::make('descrizione_evento')
                        ->label('Descrizione')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('indirizzo_evento')
                        ->label('Indirizzo evento')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DatePicker::make('data_inizio_evento')
                            ->label('Data inizio evento')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('data_fine_evento')
                            ->label('Data fine evento')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ]),
                ]),

            Forms\Components\Section::make('Logistica trasporto')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DatePicker::make('data_ritiro')
                            ->label('Data ritiro torre')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('luogo_ritiro')
                            ->label('Luogo ritiro')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('data_riconsegna')
                            ->label('Data riconsegna torre')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('luogo_riconsegna')
                            ->label('Luogo riconsegna')
                            ->maxLength(255),
                    ]),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('azienda_trasporto')
                            ->label('Azienda di trasporto')
                            ->default('Montagna Servizi')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('targa_autoveicolo')
                            ->label('Targa autoveicolo')
                            ->maxLength(20),
                    ]),
                ]),

            Forms\Components\Section::make('Responsabile in loco')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('responsabile_nome')
                            ->label('Nome e cognome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('responsabile_tipo')
                            ->label('Qualifica CAI')
                            ->required()
                            ->options(collect(ResponsabileTipo::cases())->mapWithKeys(
                                fn (ResponsabileTipo $t) => [$t->value => $t->label()]
                            )),
                    ]),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('responsabile_titolo_cai')
                            ->label('Titolo CAI')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('responsabile_codice_cai')
                            ->label('Codice CAI')
                            ->maxLength(50),
                    ]),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('responsabile_telefono')
                            ->label('Telefono')
                            ->required()
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('responsabile_email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->maxLength(255),
                    ]),
                ]),

            Forms\Components\Section::make('Allegati')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('delibera_consiglio')
                        ->label('Delibera del Consiglio Direttivo')
                        ->helperText('Obbligatoria per inviare la richiesta al GR.')
                        ->collection('delibera_consiglio')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240),

                    SpatieMediaLibraryFileUpload::make('autorizzazione_suolo_pubblico')
                        ->label('Autorizzazione suolo pubblico')
                        ->collection('autorizzazione_suolo_pubblico')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240),

                    SpatieMediaLibraryFileUpload::make('autorizzazione_ztl')
                        ->label('Autorizzazione ZTL')
                        ->collection('autorizzazione_ztl')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240),

                    SpatieMediaLibraryFileUpload::make('patente_responsabile')
                        ->label('Patente del responsabile')
                        ->collection('patente_responsabile')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240),

                    SpatieMediaLibraryFileUpload::make('altri')
                        ->label('Altri documenti')
                        ->collection('altri')
                        ->multiple()
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_inizio_prenotazione', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nome_evento')
                    ->label('Evento')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('torre.nome')
                    ->label('Torre')
                    ->badge()
                    ->color(fn (mixed $state, Prenotazione $record): string => match ($record->torre_id) {
                        1 => 'info',
                        2 => 'warning',
                        default => 'gray',
                    })
                    ->default('—'),

                Tables\Columns\TextColumn::make('data_inizio_prenotazione')
                    ->label('Da')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_fine_prenotazione')
                    ->label('A')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (PrenotazioneStatus $state): string => $state->label())
                    ->color(fn (PrenotazioneStatus $state): string => match ($state) {
                        PrenotazioneStatus::Bozza => 'gray',
                        PrenotazioneStatus::Inviata => 'warning',
                        PrenotazioneStatus::Approvata => 'success',
                        PrenotazioneStatus::Annullata => 'danger',
                        PrenotazioneStatus::InviatoPdfFirmato => 'info',
                        PrenotazioneStatus::InviatoAssicurazione => 'primary',
                        PrenotazioneStatus::Concluso => 'gray',
                    }),

                Tables\Columns\IconColumn::make('has_delibera')
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
                    ->options(collect(PrenotazioneStatus::cases())->mapWithKeys(
                        fn (PrenotazioneStatus $s) => [$s->value => $s->label()]
                    )),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Prenotazione $record): bool => auth()->user()->can('update', $record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Prenotazione $record): bool => auth()->user()->can('delete', $record)),
                Tables\Actions\Action::make('invia_richiesta')
                    ->label('Invia richiesta')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Prenotazione $record): bool => $record->status === PrenotazioneStatus::Bozza
                        && $record->hasMedia('delibera_consiglio')
                        && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Invia richiesta al GR')
                    ->modalDescription('Confermi l\'invio della richiesta? Dopo l\'invio non potrai più modificare la prenotazione.')
                    ->action(function (Prenotazione $record): void {
                        app(PrenotazioneStateMachine::class)->inviaRichiesta($record, auth()->user());
                        Notification::make()
                            ->title('Richiesta inviata')
                            ->body('La richiesta è stata inviata al GR Lombardia.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrenotazioni::route('/'),
            'create' => Pages\CreatePrenotazione::route('/create'),
            'view' => Pages\ViewPrenotazione::route('/{record}'),
            'edit' => Pages\EditPrenotazione::route('/{record}/edit'),
        ];
    }
}
