<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
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
            $panelUrl = $this->resolvePanelUrlForUser($user);

            if ($panelUrl !== null) {
                redirect()->intended($panelUrl);

                return;
            }
        }

        $this->form->fill();
    }

    public function authenticate(): ?LoginResponse
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

        return app(LoginResponse::class);
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

    private function resolvePanelUrlForUser(User $user): ?string
    {
        return match (true) {
            $user->isAdmin() => url(Filament::getPanel('admin')->getPath()),
            $user->isGrManager() => url(Filament::getPanel('gr')->getPath()),
            $user->isSezione() => url(Filament::getPanel('sezione')->getPath()),
            default => null,
        };
    }
}
