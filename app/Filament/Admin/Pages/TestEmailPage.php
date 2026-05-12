<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Services\AuditLogger;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

/**
 * @property Form $form
 */
class TestEmailPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Test email';

    protected static ?string $navigationGroup = 'Diagnostica';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.test-email';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'to' => '',
            'subject' => 'Test Prenotar',
            'body' => 'Email di test inviata dall\'admin Prenotar.',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('to')
                    ->label('Destinatario')
                    ->email()
                    ->required(),
                TextInput::make('subject')
                    ->label('Oggetto')
                    ->required(),
                Textarea::make('body')
                    ->label('Corpo')
                    ->rows(4)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        Mail::raw($data['body'], function ($message) use ($data): void {
            $message->to($data['to'])->subject($data['subject']);
        });

        app(AuditLogger::class)->logAdminAction(
            'email.test',
            null,
            'Test invio email da admin',
            ['to' => $data['to'], 'subject' => $data['subject']],
        );

        Notification::make()->title("Email di test inviata a {$data['to']}")->success()->send();
    }

    /** @return Action[] */
    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Invia email di test')
                ->action('send'),
        ];
    }
}
