<?php

declare(strict_types=1);

namespace App\Filament\Sezione\Resources\PrenotazioneResource\Pages;

use App\Enums\PrenotazioneStatus;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Models\Prenotazione;
use Filament\Forms\Components\Actions\Action as WizardAction;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\HtmlString;

class CreatePrenotazione extends CreateRecord
{
    use HasWizard;

    protected static string $resource = PrenotazioneResource::class;

    protected static string $view = 'filament.sezione.resources.prenotazione-resource.pages.create-prenotazione';

    public ?Prenotazione $prenotazioneAttiva = null;

    public function mount(): void
    {
        $this->authorizeAccess();

        $this->prenotazioneAttiva = $this->trovaPrenotazioneAttivaEsistente();

        if ($this->prenotazioneAttiva === null) {
            $this->fillForm();
        }

        $this->previousUrl = url()->previous();
    }

    /** Blocco anticipato (§BUG risolto): impedisce l'accesso al wizard se sezione/sottosezione ha già una prenotazione non conclusa/annullata. */
    private function trovaPrenotazioneAttivaEsistente(): ?Prenotazione
    {
        $user = auth()->user();

        return Prenotazione::query()
            ->attive()
            ->when(
                $user->sottosezione_id !== null,
                fn ($query) => $query->where('sottosezione_id', $user->sottosezione_id),
                fn ($query) => $query->where('sezione_id', $user->sezione_id),
            )
            ->latest('data_inizio_prenotazione')
            ->first();
    }

    public function form(Form $form): Form
    {
        $bloccaAvanzamento = fn (Get $get): bool => ! (bool) $get('manuale_step_confermato');
        $submitLabel = 'Salva come bozza';

        return $form->schema([
            Wizard::make(PrenotazioneResource::wizardSteps())
                ->view('filament.sezione.forms.components.prenotazione-wizard')
                ->viewData(fn (Get $get): array => [
                    'mobileSubmitLabel' => $submitLabel,
                    'mobileNextHint' => $bloccaAvanzamento($get)
                        ? 'Conferma la lettura del manuale per continuare'
                        : null,
                ])
                ->skippable(false)
                ->nextAction(fn (WizardAction $action) => $action
                    ->label('Continua')
                    ->disabled($bloccaAvanzamento))
                ->submitAction(new HtmlString(
                    '<button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary fi-color-custom fi-ac-btn-action px-3 py-2">'.$submitLabel.'</button>'
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
