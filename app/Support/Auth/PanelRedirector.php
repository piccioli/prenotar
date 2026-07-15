<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;
use Filament\Facades\Filament;

/**
 * Mappa un utente autenticato al pannello di sua competenza (admin/gr/sezione),
 * riusando i metodi di ruolo gia' esistenti su User — nessuna logica di ruolo
 * duplicata rispetto a User::canAccessPanel(). Espone anche l'URL del login
 * unico, riusato dalle vecchie route di login per-pannello per reindirizzare.
 */
class PanelRedirector
{
    public static function resolveUrlForUser(User $user): ?string
    {
        return match (true) {
            $user->isAdmin() => url(Filament::getPanel('admin')->getPath()),
            $user->isGrManager() => url(Filament::getPanel('gr')->getPath()),
            $user->isSezione() => url(Filament::getPanel('sezione')->getPath()),
            default => null,
        };
    }

    public static function loginUrl(): string
    {
        return Filament::getPanel('login')->getLoginUrl() ?? url('/login');
    }
}
