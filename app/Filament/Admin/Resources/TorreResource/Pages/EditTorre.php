<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TorreResource\Pages;

use App\Filament\Admin\Resources\TorreResource;
use App\Models\Torre;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTorre extends EditRecord
{
    protected static string $resource = TorreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Torre $record): void {
                    if ($record->prenotazioni()->exists()) {
                        $action->cancel();
                        Notification::make()
                            ->title('Impossibile eliminare: la torre ha prenotazioni associate.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
