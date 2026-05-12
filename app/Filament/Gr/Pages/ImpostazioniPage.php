<?php

declare(strict_types=1);

namespace App\Filament\Gr\Pages;

use App\Settings\GrSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class ImpostazioniPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Impostazioni';

    protected static ?string $title = 'Impostazioni GR Lombardia';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.gr.pages.impostazioni';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $settings = app(GrSettings::class);

        $this->form->fill([
            'emails_notifiche_gr' => $settings->emails_notifiche_gr,
            'emails_assicurazione' => $settings->emails_assicurazione,
            'presidente_nome' => $settings->presidente_nome,
            'presidente_nato_a' => $settings->presidente_nato_a,
            'presidente_data_nascita' => $settings->presidente_data_nascita,
            'firma_presidente_path' => $settings->firma_presidente_path,
            'documento_presidente_path' => $settings->documento_presidente_path,
            'giorni_minimi_caricamento_documenti' => $settings->giorni_minimi_caricamento_documenti,
            'ore_minime_richiesta_assicurazione' => $settings->ore_minime_richiesta_assicurazione,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Tabs::make('settings')
                    ->tabs([
                        Tabs\Tab::make('Email')
                            ->schema([
                                Section::make('Destinatari notifiche GR')
                                    ->description('Ricevono un\'email ad ogni nuova richiesta di prenotazione inviata da una sezione.')
                                    ->schema([
                                        TagsInput::make('emails_notifiche_gr')
                                            ->label('Indirizzi email')
                                            ->placeholder('Aggiungi email e premi Invio')
                                            ->splitKeys(['Enter', 'Tab', ','])
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Destinatari email assicurazione')
                                    ->description('Ricevono il Modulo 3 firmato all\'invio assicurazione.')
                                    ->schema([
                                        TagsInput::make('emails_assicurazione')
                                            ->label('Indirizzi email')
                                            ->placeholder('Aggiungi email e premi Invio')
                                            ->splitKeys(['Enter', 'Tab', ','])
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Presidente GR')
                            ->schema([
                                Section::make('Anagrafica')
                                    ->description('Dati del presidente del GR Lombardia, usati per la firma del Modulo 3.')
                                    ->schema([
                                        TextInput::make('presidente_nome')
                                            ->label('Nome e cognome')
                                            ->maxLength(255),

                                        TextInput::make('presidente_nato_a')
                                            ->label('Luogo di nascita')
                                            ->maxLength(255),

                                        DatePicker::make('presidente_data_nascita')
                                            ->label('Data di nascita')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->format('Y-m-d'),
                                    ])->columns(3),

                                Section::make('Documenti')
                                    ->description('Firma e carta d\'identità del presidente, allegati al Modulo 3 assicurazione.')
                                    ->schema([
                                        FileUpload::make('firma_presidente_path')
                                            ->label('Firma (JPG/PNG)')
                                            ->image()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                            ->disk('local')
                                            ->directory('gr/presidente')
                                            ->visibility('private')
                                            ->maxSize(2048),

                                        FileUpload::make('documento_presidente_path')
                                            ->label('Carta d\'identità (PDF/JPG/PNG)')
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                            ->disk('local')
                                            ->directory('gr/presidente')
                                            ->visibility('private')
                                            ->maxSize(5120),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Parametri operativi')
                            ->schema([
                                Section::make('Vincoli temporali')
                                    ->schema([
                                        TextInput::make('giorni_minimi_caricamento_documenti')
                                            ->label('Giorni minimi anticipo richiesta')
                                            ->helperText('Numero di giorni prima dell\'evento entro cui le sezioni devono inviare la richiesta.')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(120)
                                            ->required(),

                                        TextInput::make('ore_minime_richiesta_assicurazione')
                                            ->label('Ore minime preavviso assicurazione')
                                            ->helperText('Ore di preavviso minime per l\'invio del Modulo 3 all\'assicurazione.')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(720)
                                            ->required(),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Salva impostazioni')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = app(GrSettings::class);
        $settings->emails_notifiche_gr = $data['emails_notifiche_gr'] ?? [];
        $settings->emails_assicurazione = $data['emails_assicurazione'] ?? [];
        $settings->presidente_nome = filled($data['presidente_nome']) ? $data['presidente_nome'] : null;
        $settings->presidente_nato_a = filled($data['presidente_nato_a']) ? $data['presidente_nato_a'] : null;
        $settings->presidente_data_nascita = filled($data['presidente_data_nascita']) ? $data['presidente_data_nascita'] : null;
        $settings->firma_presidente_path = filled($data['firma_presidente_path']) ? $data['firma_presidente_path'] : null;
        $settings->documento_presidente_path = filled($data['documento_presidente_path']) ? $data['documento_presidente_path'] : null;
        $settings->giorni_minimi_caricamento_documenti = (int) $data['giorni_minimi_caricamento_documenti'];
        $settings->ore_minime_richiesta_assicurazione = (int) $data['ore_minime_richiesta_assicurazione'];
        $settings->save();

        Notification::make()
            ->title('Impostazioni salvate')
            ->success()
            ->send();
    }
}
