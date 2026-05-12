<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ExcelImportResource\Pages;

use App\Filament\Admin\Resources\ExcelImportResource;
use App\Services\Import\ExcelImportService;
use App\Services\Import\Exceptions\ImportAlreadyDoneException;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListExcelImports extends ListRecords
{
    protected static string $resource = ExcelImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadNew')
                ->label('Carica nuovo Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel (.xlsx)')
                        ->disk('local')
                        ->directory('excel-imports-tmp')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required(),
                    Toggle::make('force')
                        ->label('Re-importa anche se già elaborato')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $relativePath = $data['file'];
                    $absolutePath = Storage::disk('local')->path($relativePath);

                    try {
                        $result = app(ExcelImportService::class)->import($absolutePath, auth()->id(), (bool) $data['force']);

                        Notification::make()
                            ->title("Import completato: {$result->righeImportate} importate, {$result->userCreati} nuovi utenti, {$result->righeInErrore} errori.")
                            ->success()
                            ->send();
                    } catch (ImportAlreadyDoneException) {
                        Notification::make()
                            ->title('File già importato in precedenza. Usa "Re-importa" per forzare.')
                            ->warning()
                            ->send();
                    } finally {
                        Storage::disk('local')->delete($relativePath);
                    }
                }),
        ];
    }
}
