<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class FirstAccessPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = null;

    protected static string $view = 'filament.pages.first-access';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $contact_email = null;

    public function mount(): void
    {
        $this->contact_email = auth()->user()?->contact_email;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('contact_email')
                    ->label('La tua email di contatto reale')
                    ->helperText('Questa email riceverà le notifiche di Prenotar al posto dell\'indirizzo sintetico.')
                    ->email()
                    ->required()
                    ->maxLength(255),
            ])
            ->statePath('');
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Salva e continua')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = auth()->user();
        $user->update(['contact_email' => $data['contact_email']]);

        Notification::make()
            ->title('Email di contatto salvata')
            ->success()
            ->send();

        $this->redirect(Filament::getUrl());
    }
}
