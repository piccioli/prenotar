<?php

declare(strict_types=1);

namespace App\Filament\Http\Responses\Auth;

use App\Models\User;
use App\Support\Auth\PanelRedirector;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Sostituisce il redirect stock di Filament (che punta al pannello corrente,
 * cioe' il pannello neutro "login") con un redirect basato sul ruolo
 * dell'utente appena autenticato.
 */
class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        $panelUrl = ($user instanceof User) ? PanelRedirector::resolveUrlForUser($user) : null;

        return redirect()->intended($panelUrl ?? Filament::getUrl());
    }
}
