<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Filament\Http\Responses\Auth\LoginResponse;
use App\Models\User;
use App\Support\Auth\PanelRedirector;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;

/**
 * Login unico su /login: stessa UX/funzionalita' del componente Filament
 * stock, ma l'autorizzazione non e' legata a un pannello specifico (questo
 * pannello "login" non ha risorse/pagine proprie) — riusa canAccessPanel()
 * sui 3 pannelli reali invece che sul pannello corrente.
 */
class Login extends BaseLogin
{
    public function mount(): void
    {
        $user = Filament::auth()->user();

        if ($user instanceof User) {
            $panelUrl = PanelRedirector::resolveUrlForUser($user);

            if ($panelUrl !== null) {
                redirect()->intended($panelUrl);

                return;
            }
        }

        $this->form->fill();
    }

    public function authenticate(): ?LoginResponseContract
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (($user instanceof FilamentUser) && (! $this->userCanAccessAnyPanel($user))) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return new LoginResponse;
    }

    private function userCanAccessAnyPanel(FilamentUser $user): bool
    {
        foreach (['admin', 'gr', 'sezione'] as $panelId) {
            if ($user->canAccessPanel(Filament::getPanel($panelId))) {
                return true;
            }
        }

        return false;
    }
}
