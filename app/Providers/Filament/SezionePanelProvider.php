<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\FirstAccessPage;
use App\Filament\Sezione\Widgets\PrenotazioniDashboardWidget;
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
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class SezionePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sezione')
            ->path('sezione')
            ->login()
            ->passwordReset()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName('Prenotar — Sezione')
            ->brandLogo(asset('images/cai-lombardia-placeholder.svg'))
            ->plugins([
                FilamentFullCalendarPlugin::make(),
            ])
            ->discoverResources(in: app_path('Filament/Sezione/Resources'), for: 'App\\Filament\\Sezione\\Resources')
            ->discoverPages(in: app_path('Filament/Sezione/Pages'), for: 'App\\Filament\\Sezione\\Pages')
            ->pages([
                Pages\Dashboard::class,
                FirstAccessPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Sezione/Widgets'), for: 'App\\Filament\\Sezione\\Widgets')
            ->widgets([
                PrenotazioniDashboardWidget::class,
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
