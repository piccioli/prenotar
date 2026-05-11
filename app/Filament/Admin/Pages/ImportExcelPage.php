<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\ExcelImport;
use App\Services\Import\ExcelImportService;
use App\Services\Import\Exceptions\ImportAlreadyDoneException;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/**
 * @property Form $form
 */
class ImportExcelPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Import Excel';

    protected static ?string $navigationGroup = 'Gestione';

    protected static string $view = 'filament.admin.pages.import-excel';

    protected static ?int $navigationSort = 10;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('xlsx_file')
                    ->label('File Excel sezioni/sottosezioni')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->required()
                    ->maxSize(5120)
                    ->disk('local')
                    ->directory('imports'),
                Checkbox::make('force')
                    ->label('Re-import anche se il file è già stato processato'),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ExcelImport::query()->latest()->limit(10))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('filename')
                    ->label('File')
                    ->limit(40),
                TextColumn::make('righe_importate')
                    ->label('Importate')
                    ->numeric(),
                TextColumn::make('righe_aggiornate')
                    ->label('Aggiornate')
                    ->numeric(),
                TextColumn::make('righe_in_errore')
                    ->label('Errori')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('importedBy.name')
                    ->label('Da'),
            ])
            ->paginated(false);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $uploadedPath = $data['xlsx_file'] ?? null;
        if ($uploadedPath === null) {
            Notification::make()->title('Nessun file selezionato')->danger()->send();

            return;
        }

        $absolutePath = Storage::disk('local')->path($uploadedPath);

        try {
            $result = app(ExcelImportService::class)->import(
                absolutePath: $absolutePath,
                importedById: auth()->id(),
                force: (bool) ($data['force'] ?? false),
            );

            $msg = "Sezioni/sottosezioni importate: {$result->righeImportate}, aggiornate: {$result->righeAggiornate}, user creati: {$result->userCreati}.";

            if ($result->righeInErrore > 0) {
                Notification::make()
                    ->title("Import completato con {$result->righeInErrore} errori")
                    ->body($msg)
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Import completato con successo')
                    ->body($msg)
                    ->success()
                    ->send();
            }

            $this->form->fill();
        } catch (ImportAlreadyDoneException $e) {
            Notification::make()
                ->title('File già importato')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }
    }
}
