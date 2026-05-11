<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Gr\Widgets\PlaceholderWidget;
use App\Filament\Pages\FirstAccessPage;
use App\Http\Middleware\EnsureContactEmail;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class GrPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('gr')
            ->path('gr')
            ->login()
            ->passwordReset()
            ->colors([
                'primary' => Color::Green,
            ])
            ->brandName('Prenotar — GR Lombardia')
            ->brandLogo(asset('images/cai-lombardia-placeholder.svg'))
            ->discoverResources(in: app_path('Filament/Gr/Resources'), for: 'App\\Filament\\Gr\\Resources')
            ->discoverPages(in: app_path('Filament/Gr/Pages'), for: 'App\\Filament\\Gr\\Pages')
            ->pages([
                Pages\Dashboard::class,
                FirstAccessPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Gr/Widgets'), for: 'App\\Filament\\Gr\\Widgets')
            ->widgets([
                PlaceholderWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureContactEmail::class,
            ]);
    }
}
