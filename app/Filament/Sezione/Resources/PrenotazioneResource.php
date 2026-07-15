<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources;

use App\Enums\PrenotazioneStatus;
use App\Enums\ResponsabileTipo;
use App\Filament\Sezione\Resources\PrenotazioneResource\Pages;
use App\Filament\Sezione\Widgets\CalendarioPrenotazioniWidget;
use App\Models\Prenotazione;
use App\Models\Torre;
use App\Rules\DataRitiroEntroInizioPrenotazione;
use App\Rules\NoOverlapTorre;
use App\Rules\UnicaPrenotazioneAttivaPerUser;
use App\Services\PrenotazioneStateMachine;
use App\Settings\GrSettings;
use App\Support\Testing\PrenotazioneWizardAutofill;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

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

    /** @param callable(Forms\Set, Forms\Get): void $autofill */
    private static function autofillAction(string $name, callable $autofill): Forms\Components\Actions
    {
        return Forms\Components\Actions::make([
            Forms\Components\Actions\Action::make($name)
                ->label('Compila con dati di test')
                ->icon('heroicon-o-beaker')
                ->color('gray')
                ->visible(fn (): bool => app()->environment('local'))
                ->action(function (Forms\Set $set, Forms\Get $get) use ($autofill): void {
                    abort_unless(app()->environment('local'), 404);

                    $autofill($set, $get);

                    Notification::make()->title('Dati di test inseriti')->success()->send();
                }),
        ])->fullWidth();
    }

    /** @return list<Forms\Components\Wizard\Step> */
    public static function wizardSteps(): array
    {
        return [
            Forms\Components\Wizard\Step::make('Manuale d\'istruzioni')
                ->icon('heroicon-o-book-open')
                ->schema([
                    self::autofillAction('autofill_manuale', [PrenotazioneWizardAutofill::class, 'manuale']),

                    Forms\Components\Section::make('Prima di iniziare, leggi il manuale d\'istruzioni')
                        ->description('La conferma di lettura è obbligatoria per procedere, qualunque torre sceglierai nello step successivo.')
                        ->schema([
                            Forms\Components\Checkbox::make('manuale_step_confermato')
                                ->hiddenLabel()
                                ->live()
                                ->dehydrated(false)
                                ->default(false)
                                ->rules(['accepted'])
                                ->validationMessages([
                                    'accepted' => 'Devi confermare di aver letto il manuale d\'istruzioni prima di proseguire.',
                                ])
                                ->viewData(['torre' => self::torreManualeRiferimento()])
                                ->view('filament.sezione.forms.components.manuale-istruzioni-step')
                                ->columnSpanFull(),
                        ]),
                ]),

            Forms\Components\Wizard\Step::make('Quando & dove')
                ->icon('heroicon-o-calendar')
                ->schema([
                    self::autofillAction('autofill_quando_dove', [PrenotazioneWizardAutofill::class, 'quandoDove']),

                    Forms\Components\Section::make('Quando ti serve la torre?')
                        ->description('Indica il periodo di utilizzo. La disponibilità qui sotto si aggiorna in base alle date.')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\DatePicker::make('data_inizio_prenotazione')
                                    ->label('Data inizio utilizzo')
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
                                    ->label('Data fine utilizzo')
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
                            ]),

                            Forms\Components\Radio::make('torre_id')
                                ->label('Quale torre preferisci?')
                                ->helperText('Facoltativo: se non scegli, sarà il Gruppo Regionale ad assegnarla.')
                                ->options(fn (): array => ['' => 'Nessuna preferenza']
                                    + Torre::where('is_active', true)->pluck('nome', 'id')->all())
                                ->live()
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) $state : null)
                                ->rules(fn (Forms\Get $get): array => [
                                    new NoOverlapTorre(
                                        torreId: $get('torre_id') ? (int) $get('torre_id') : null,
                                        dataInizio: (string) ($get('data_inizio_prenotazione') ?? ''),
                                        dataFine: (string) ($get('data_fine_prenotazione') ?? ''),
                                    ),
                                ])
                                ->view('filament.sezione.forms.components.torre-radio-cards')
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Section::make('Disponibilità')
                        ->description('I giorni occupati sono evidenziati per torre nel calendario qui sotto.')
                        ->schema([
                            Forms\Components\Livewire::make(CalendarioPrenotazioniWidget::class)
                                ->columnSpanFull(),
                        ]),
                ]),

            Forms\Components\Wizard\Step::make('Evento')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    self::autofillAction('autofill_evento', [PrenotazioneWizardAutofill::class, 'evento']),

                    Forms\Components\Section::make('Racconta l\'evento')
                        ->description('Queste informazioni appariranno nella richiesta e sui documenti generati.')
                        ->schema([
                            Forms\Components\TextInput::make('nome_evento')
                                ->label('Nome evento')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-megaphone'),

                            Forms\Components\Select::make('tipo_evento')
                                ->label('Tipo evento')
                                ->required()
                                ->options(self::tipoEventoOptions())
                                ->prefixIcon('heroicon-o-tag'),

                            Forms\Components\Textarea::make('descrizione_evento')
                                ->label('Descrizione')
                                ->rows(3)
                                ->maxLength(2000)
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Section::make('Dove e quando si svolge')
                        ->schema([
                            Forms\Components\TextInput::make('indirizzo_evento')
                                ->label('Indirizzo evento')
                                ->required()
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-map-pin')
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\DatePicker::make('data_inizio_evento')
                                    ->label('Data inizio evento')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->minDate(fn (Forms\Get $get) => $get('data_inizio_prenotazione')),

                                Forms\Components\DatePicker::make('data_fine_evento')
                                    ->label('Data fine evento')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->minDate(fn (Forms\Get $get) => $get('data_inizio_evento')),
                            ]),
                        ]),
                ]),

            Forms\Components\Wizard\Step::make('Logistica trasporto')
                ->icon('heroicon-o-truck')
                ->schema([
                    self::autofillAction('autofill_logistica_trasporto', [PrenotazioneWizardAutofill::class, 'logisticaTrasporto']),

                    Forms\Components\Section::make('Come trasporterai la torre?')
                        ->description('Date di ritiro e riconsegna presso il deposito, mezzo e conducente.')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\DatePicker::make('data_ritiro')
                                    ->label('Data ritiro torre')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->rules(fn (Forms\Get $get): array => [
                                        new DataRitiroEntroInizioPrenotazione(
                                            dataInizioPrenotazione: (string) ($get('data_inizio_prenotazione') ?? ''),
                                        ),
                                    ]),

                                Forms\Components\DatePicker::make('data_riconsegna')
                                    ->label('Data riconsegna torre')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->helperText('È la stessa data mostrata nel calendario delle torri.'),

                                Forms\Components\TextInput::make('targa_autoveicolo')
                                    ->label('Targa autoveicolo')
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-identification'),

                                Forms\Components\TextInput::make('nome_conducente')
                                    ->label('Nome conducente')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user'),
                            ]),
                        ]),

                    Forms\Components\Section::make('Dichiarazione patente')
                        ->schema([
                            Forms\Components\Checkbox::make('patente_be_confermata')
                                ->label('Dichiaro, sotto la mia responsabilità, di essere in possesso di patente di guida di categoria B+E (o superiore), idonea al traino del rimorchio della torre di arrampicata, e che quanto dichiarato corrisponde al vero.')
                                ->live()
                                ->dehydrated(false)
                                ->default(false)
                                ->rules(['accepted'])
                                ->validationMessages([
                                    'accepted' => 'Devi confermare il possesso della patente B+E per proseguire.',
                                ])
                                ->afterStateUpdated(fn (?bool $state, Forms\Set $set) => $set('patente_be_dichiarata_at', $state ? now() : null)),

                            Forms\Components\Hidden::make('patente_be_dichiarata_at'),
                        ]),
                ]),

            Forms\Components\Wizard\Step::make('Responsabile in loco')
                ->icon('heroicon-o-user')
                ->schema([
                    self::autofillAction('autofill_responsabile_in_loco', [PrenotazioneWizardAutofill::class, 'responsabileInLoco']),

                    Forms\Components\Section::make('Chi è il responsabile in loco?')
                        ->description('La persona di riferimento per la torre durante l\'evento.')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('responsabile_nome')
                                    ->label('Nome e cognome')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user'),

                                Forms\Components\Select::make('responsabile_tipo')
                                    ->label('Qualifica CAI')
                                    ->required()
                                    ->options(collect(ResponsabileTipo::cases())->mapWithKeys(
                                        fn (ResponsabileTipo $t) => [$t->value => $t->label()]
                                    ))
                                    ->prefixIcon('heroicon-o-identification'),
                            ]),

                            Forms\Components\TextInput::make('responsabile_titolo_cai')
                                ->label('Titolo CAI')
                                ->maxLength(255)
                                ->prefixIcon('heroicon-o-academic-cap'),
                        ]),

                    Forms\Components\Section::make('Contatti')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('responsabile_telefono')
                                    ->label('Telefono')
                                    ->required()
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-phone'),

                                Forms\Components\TextInput::make('responsabile_email')
                                    ->label('Email')
                                    ->required()
                                    ->email()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-envelope'),
                            ]),
                        ]),
                ]),

            Forms\Components\Wizard\Step::make('Riepilogo')
                ->icon('heroicon-o-check-circle')
                ->schema([
                    Forms\Components\Placeholder::make('riepilogo')
                        ->hiddenLabel()
                        ->content(fn (Forms\Get $get): Htmlable => new HtmlString(
                            view('filament.sezione.forms.components.riepilogo', [
                                'gruppi' => self::gruppiRiepilogo($get),
                            ])->render()
                        )),

                    Forms\Components\Placeholder::make('avviso_delibera')
                        ->hiddenLabel()
                        ->content(new HtmlString(
                            view('filament.sezione.forms.components.avviso-delibera')->render()
                        )),
                ]),
        ];
    }

    /** @return list<array{icon: string, titolo: string, stepId: string, righe: list<array<string, mixed>>}> */
    private static function gruppiRiepilogo(Forms\Get $get): array
    {
        $torre = Torre::find($get('torre_id'));

        return [
            [
                'icon' => 'heroicon-o-calendar-days',
                'titolo' => 'Quando & dove',
                'stepId' => 'quando-dove',
                'righe' => [
                    ['tipo' => 'testo', 'label' => 'Periodo di utilizzo', 'valore' => self::formattaPeriodo($get('data_inizio_prenotazione'), $get('data_fine_prenotazione'))],
                    ['tipo' => 'torre', 'label' => 'Torre richiesta', 'torre' => $torre],
                    ['tipo' => 'testo', 'label' => 'Deposito torre', 'valore' => self::depositoTorre($torre)],
                    ['tipo' => 'manuale', 'label' => 'Manuale d\'istruzioni', 'torre' => $torre, 'confermato' => (bool) $get('manuale_step_confermato')],
                ],
            ],
            [
                'icon' => 'heroicon-o-flag',
                'titolo' => 'Evento',
                'stepId' => 'evento',
                'righe' => [
                    ['tipo' => 'testo', 'label' => 'Nome evento', 'valore' => $get('nome_evento') ?: '—'],
                    ['tipo' => 'testo', 'label' => 'Tipo', 'valore' => self::labelTipoEvento($get('tipo_evento'))],
                    ['tipo' => 'testo', 'label' => 'Indirizzo', 'valore' => $get('indirizzo_evento') ?: '—'],
                    ['tipo' => 'testo', 'label' => 'Date evento', 'valore' => self::formattaPeriodo($get('data_inizio_evento'), $get('data_fine_evento'))],
                ],
            ],
            [
                'icon' => 'heroicon-o-truck',
                'titolo' => 'Logistica trasporto',
                'stepId' => 'logistica-trasporto',
                'righe' => [
                    ['tipo' => 'testo', 'label' => 'Ritiro', 'valore' => self::formattaData($get('data_ritiro'))],
                    ['tipo' => 'testo', 'label' => 'Riconsegna', 'valore' => self::formattaData($get('data_riconsegna'))],
                    ['tipo' => 'testo', 'label' => 'Targa autoveicolo', 'valore' => $get('targa_autoveicolo') ?: '—'],
                    ['tipo' => 'testo', 'label' => 'Conducente', 'valore' => $get('nome_conducente') ?: '—'],
                    ['tipo' => 'testo', 'label' => 'Patente B+E', 'valore' => filled($get('patente_be_dichiarata_at')) ? 'Dichiarata' : 'Da confermare'],
                ],
            ],
            [
                'icon' => 'heroicon-o-user',
                'titolo' => 'Responsabile in loco',
                'stepId' => 'responsabile-in-loco',
                'righe' => [
                    ['tipo' => 'testo', 'label' => 'Nome', 'valore' => $get('responsabile_nome') ?: '—'],
                    ['tipo' => 'testo', 'label' => 'Tipo e titolo CAI', 'valore' => self::formattaResponsabileTipo($get('responsabile_tipo'), $get('responsabile_titolo_cai'))],
                    ['tipo' => 'testo', 'label' => 'Contatti', 'valore' => self::formattaContatti($get('responsabile_telefono'), $get('responsabile_email'))],
                ],
            ],
        ];
    }

    public static function torreManualeRiferimento(): ?Torre
    {
        return Torre::where('is_active', true)
            ->whereNotNull('manuale_pdf_path')
            ->orderBy('id')
            ->first();
    }

    private static function depositoTorre(?Torre $torre): string
    {
        return $torre === null ? '—' : (string) $torre->indirizzo_deposito;
    }

    private static function formattaPeriodo(?string $inizio, ?string $fine): string
    {
        $formatta = fn (?string $valore): ?string => filled($valore) ? Carbon::parse($valore)->format('d/m/Y') : null;
        $parti = array_filter([$formatta($inizio), $formatta($fine)]);

        return $parti === [] ? '—' : implode(' → ', $parti);
    }

    private static function formattaData(?string $data): string
    {
        return filled($data) ? Carbon::parse($data)->format('d/m/Y') : '—';
    }

    private static function formattaResponsabileTipo(?string $tipo, ?string $titolo): string
    {
        $parti = array_filter([ResponsabileTipo::tryFrom((string) $tipo)?->label(), $titolo]);

        return $parti === [] ? '—' : implode(' · ', $parti);
    }

    private static function formattaContatti(?string $telefono, ?string $email): string
    {
        $parti = array_filter([$telefono, $email]);

        return $parti === [] ? '—' : implode(' · ', $parti);
    }

    /** @return array<string, string> */
    private static function tipoEventoOptions(): array
    {
        return [
            'fiera' => 'Fiera',
            'manifestazione_cai' => 'Manifestazione CAI',
            'evento_promozionale' => 'Evento promozionale',
            'corso' => 'Corso',
            'altro' => 'Altro',
        ];
    }

    private static function labelTipoEvento(?string $value): string
    {
        return self::tipoEventoOptions()[$value] ?? '—';
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
                        ->options(self::tipoEventoOptions()),

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
                            ->displayFormat('d/m/Y')
                            ->rules(fn (Forms\Get $get): array => [
                                new DataRitiroEntroInizioPrenotazione(
                                    dataInizioPrenotazione: (string) ($get('data_inizio_prenotazione') ?? ''),
                                ),
                            ]),

                        Forms\Components\DatePicker::make('data_riconsegna')
                            ->label('Data riconsegna torre')
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('targa_autoveicolo')
                            ->label('Targa autoveicolo')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('nome_conducente')
                            ->label('Nome conducente')
                            ->required()
                            ->maxLength(255),
                    ]),

                    Forms\Components\Checkbox::make('patente_be_confermata')
                        ->label('Dichiaro, sotto la mia responsabilità, di essere in possesso di patente di guida di categoria B+E (o superiore), idonea al traino del rimorchio della torre di arrampicata, e che quanto dichiarato corrisponde al vero.')
                        ->live()
                        ->dehydrated(false)
                        ->rules(['accepted'])
                        ->validationMessages([
                            'accepted' => 'Devi confermare il possesso della patente B+E per proseguire.',
                        ])
                        ->afterStateHydrated(fn (Forms\Components\Checkbox $component, ?Prenotazione $record) => $component->state(filled($record?->patente_be_dichiarata_at)))
                        ->afterStateUpdated(fn (?bool $state, Forms\Set $set) => $set('patente_be_dichiarata_at', $state ? now() : null)),

                    Forms\Components\Hidden::make('patente_be_dichiarata_at'),
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

                    Forms\Components\TextInput::make('responsabile_titolo_cai')
                        ->label('Titolo CAI')
                        ->maxLength(255),

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

            Forms\Components\Grid::make(['default' => 1, 'lg' => 3])
                ->schema([
                    Forms\Components\Group::make([
                        Forms\Components\Section::make('Allegati')
                            ->description('La delibera del consiglio è obbligatoria per inviare la richiesta.')
                            ->schema([
                                self::allegatoField(
                                    name: 'delibera_consiglio',
                                    collection: 'delibera_consiglio',
                                    titolo: 'Delibera del Consiglio Direttivo',
                                    obbligatorio: true,
                                    sottotitoloMancante: 'Non ancora caricata — obbligatoria per l\'invio al GR',
                                    iconaMancante: 'heroicon-o-exclamation-triangle',
                                ),
                                self::allegatoField(
                                    name: 'autorizzazione_suolo_pubblico',
                                    collection: 'autorizzazione_suolo_pubblico',
                                    titolo: 'Autorizzazione suolo pubblico',
                                    obbligatorio: false,
                                    sottotitoloMancante: 'Facoltativa — richiesta se l\'evento occupa suolo pubblico',
                                    iconaMancante: 'heroicon-o-document-plus',
                                ),
                                self::allegatoField(
                                    name: 'autorizzazione_ztl',
                                    collection: 'autorizzazione_ztl',
                                    titolo: 'Autorizzazione ZTL',
                                    obbligatorio: false,
                                    sottotitoloMancante: 'Facoltativa — solo se l\'evento è in zona a traffico limitato',
                                    iconaMancante: 'heroicon-o-document-plus',
                                ),
                                self::allegatoField(
                                    name: 'patente_responsabile',
                                    collection: 'patente_responsabile',
                                    titolo: 'Patente del responsabile',
                                    obbligatorio: false,
                                    sottotitoloMancante: 'Richiesta se il trasporto avviene con mezzo privato',
                                    iconaMancante: 'heroicon-o-identification',
                                ),
                                self::allegatoField(
                                    name: 'altri',
                                    collection: 'altri',
                                    titolo: 'Altri allegati',
                                    obbligatorio: false,
                                    sottotitoloMancante: 'Documenti aggiuntivi utili alla valutazione',
                                    iconaMancante: 'heroicon-o-paper-clip',
                                    multiple: true,
                                ),
                            ])
                            ->collapsible(),
                    ])->columnSpan(['lg' => 2]),

                    Forms\Components\Group::make([
                        self::azioneInvioSection(),
                        self::azioneEliminaSection(),
                    ])->columnSpan(['lg' => 1]),
                ]),
        ]);
    }

    private static function allegatoField(
        string $name,
        string $collection,
        string $titolo,
        bool $obbligatorio,
        string $sottotitoloMancante,
        string $iconaMancante,
        bool $multiple = false,
    ): SpatieMediaLibraryFileUpload {
        return SpatieMediaLibraryFileUpload::make($name)
            ->hiddenLabel()
            ->collection($collection)
            ->multiple($multiple)
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->maxSize(10240)
            ->view('filament.sezione.forms.components.allegato-file-upload')
            ->viewData([
                'titolo' => $titolo,
                'obbligatorio' => $obbligatorio,
                'sottotitoloMancante' => $sottotitoloMancante,
                'iconaMancante' => $iconaMancante,
            ]);
    }

    private static function azioneInvioSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Invio al Gruppo Regionale')
            ->schema([
                Forms\Components\Placeholder::make('invio_hint')
                    ->hiddenLabel()
                    ->content(fn (?Prenotazione $record): HtmlString => new HtmlString(
                        view('filament.sezione.forms.components.azione-invio-hint', [
                            'bloccato' => ! ($record?->hasMedia('delibera_consiglio') ?? false),
                        ])->render()
                    )),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('invia_richiesta')
                        ->label('Invia richiesta al GR')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->disabled(fn (?Prenotazione $record): bool => ! ($record?->hasMedia('delibera_consiglio') ?? false))
                        ->requiresConfirmation()
                        ->modalHeading('Invia richiesta al GR')
                        ->modalDescription('Confermi l\'invio della richiesta? Dopo l\'invio non potrai più modificare la prenotazione.')
                        ->action(function (?Prenotazione $record, Forms\Components\Actions\Action $action): void {
                            if ($record === null) {
                                return;
                            }

                            app(PrenotazioneStateMachine::class)->inviaRichiesta($record, auth()->user());

                            Notification::make()
                                ->title('Richiesta inviata')
                                ->body('La richiesta è stata inviata al GR Lombardia.')
                                ->success()
                                ->send();

                            $action->redirect(PrenotazioneResource::getUrl('view', ['record' => $record]));
                        }),
                ])->fullWidth(),
            ]);
    }

    private static function azioneEliminaSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Zona pericolosa')
            ->description('Puoi eliminare questa richiesta solo finché è in Bozza. L\'operazione non è reversibile.')
            ->visible(fn (?Prenotazione $record): bool => $record?->status === PrenotazioneStatus::Bozza)
            ->extraAttributes(['style' => 'border-color:#E7B7B0'])
            ->schema([
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('elimina_bozza')
                        ->label('Elimina bozza')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (?Prenotazione $record, Forms\Components\Actions\Action $action): void {
                            if ($record === null) {
                                return;
                            }

                            $record->delete();

                            Notification::make()->title('Bozza eliminata')->success()->send();

                            $action->redirect(PrenotazioneResource::getUrl('index'));
                        }),
                ])->fullWidth(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_inizio_prenotazione', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nome_evento')
                    ->label('Evento')
                    ->weight('bold')
                    ->searchable()
                    ->limit(40)
                    ->description(fn (Prenotazione $record): string => collect([
                        self::labelTipoEvento($record->tipo_evento),
                        $record->indirizzo_evento,
                    ])->filter()->implode(' · ')),

                Tables\Columns\TextColumn::make('periodo')
                    ->label('Periodo')
                    ->getStateUsing(fn (Prenotazione $record): string => self::formattaPeriodo(
                        $record->data_inizio_prenotazione->toDateString(),
                        $record->data_fine_prenotazione->toDateString(),
                    ))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('data_inizio_prenotazione', $direction)),

                Tables\Columns\TextColumn::make('torre.nome')
                    ->label('Torre')
                    ->badge()
                    ->color(fn (Prenotazione $record): array => Color::hex(Torre::coloreHexPer($record->torre)))
                    ->default('Da assegnare'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (PrenotazioneStatus $state): string => $state->label())
                    ->color(fn (PrenotazioneStatus $state): string => $state->color()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options(collect(PrenotazioneStatus::cases())->mapWithKeys(
                        fn (PrenotazioneStatus $s) => [$s->value => $s->label()]
                    )),

                SelectFilter::make('torre_id')
                    ->label('Torre')
                    ->options(Torre::where('is_active', true)->pluck('nome', 'id')),
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
            ->bulkActions([])
            ->emptyStateHeading('Nessuna prenotazione')
            ->emptyStateDescription('Non ci sono prenotazioni che corrispondono ai criteri di ricerca.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
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
