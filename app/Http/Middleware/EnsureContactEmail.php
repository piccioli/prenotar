<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContactEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! $user->email_is_fallback || $user->contact_email !== null) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        // Permetti le route di auth e la pagina first-access stessa
        $allowed = [
            'filament.admin.pages.first-access-page',
            'filament.gr.pages.first-access-page',
            'filament.sezione.pages.first-access-page',
        ];

        if (str_starts_with($routeName, 'filament.') && str_contains($routeName, '.auth.')) {
            return $next($request);
        }

        if (in_array($routeName, $allowed, true)) {
            return $next($request);
        }

        $panelId = Filament::getCurrentPanel()?->getId() ?? 'sezione';

        return redirect()->route("filament.{$panelId}.pages.first-access-page");
    }
}
