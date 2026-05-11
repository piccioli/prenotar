<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources\PrenotazioneResource\Pages;

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\HtmlString;

class CreatePrenotazione extends CreateRecord
{
    use HasWizard;

    protected static string $resource = PrenotazioneResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make(PrenotazioneResource::wizardSteps())
                ->skippable(false)
                ->submitAction(new HtmlString(
                    '<button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary fi-color-custom fi-ac-btn-action px-3 py-2">Salva come bozza</button>'
                )),
        ])->statePath('data');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['user_id'] = $user->id;
        $data['sezione_id'] = $user->sezione_id;
        $data['sottosezione_id'] = $user->sottosezione_id;
        $data['status'] = PrenotazioneStatus::Bozza;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return PrenotazioneResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Bozza creata')
            ->body('Carica la delibera del consiglio per poter inviare la richiesta al GR.')
            ->success();
    }
}
