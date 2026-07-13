<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\FirstAccessPage;
use App\Filament\Sezione\Pages\CalendarioPage;
use App\Filament\Sezione\Resources\PrenotazioneResource;
use App\Filament\Sezione\Resources\PrenotazioneResource\Pages\ListPrenotazioni;
use App\Filament\Sezione\Widgets\PrenotazioniDashboardWidget;
use App\Http\Middleware\EnsureContactEmail;
use App\Support\Auth\PanelRedirector;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
            ->login(fn () => redirect(PanelRedirector::loginUrl()))
            ->passwordReset()
            ->colors([
                'primary' => Color::hex('#2E5878'),
            ])
            ->viteTheme('resources/css/filament/sezione/theme.css')
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
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => view('filament.components.mobile-topbar-brand')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('filament.components.mobile-topbar-notifications')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.components.mobile-bottom-nav', [
                    'items' => self::mobileNavItems(),
                ])->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.components.mobile-fab', [
                    'href' => PrenotazioneResource::getUrl('create'),
                    'label' => 'Nuova prenotazione',
                ])->render(),
                scopes: [ListPrenotazioni::class],
            )
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

    /**
     * @return array<int, array{label: string, icon: string, url: string, active: bool}>
     */
    private static function mobileNavItems(): array
    {
        return [
            [
                'label' => 'Home',
                'icon' => 'heroicon-o-home',
                'url' => Pages\Dashboard::getUrl(),
                'active' => request()->routeIs('filament.sezione.pages.dashboard'),
            ],
            [
                'label' => 'Nuova',
                'icon' => 'heroicon-o-plus',
                'url' => PrenotazioneResource::getUrl('create'),
                'active' => request()->routeIs('filament.sezione.resources.prenotaziones.create'),
            ],
            [
                'label' => 'Prenotazioni',
                'icon' => 'heroicon-o-clipboard-document-list',
                'url' => PrenotazioneResource::getUrl(),
                'active' => request()->routeIs([
                    'filament.sezione.resources.prenotaziones.index',
                    'filament.sezione.resources.prenotaziones.view',
                    'filament.sezione.resources.prenotaziones.edit',
                ]),
            ],
            [
                'label' => 'Calendario',
                'icon' => 'heroicon-o-calendar-days',
                'url' => CalendarioPage::getUrl(),
                'active' => request()->routeIs('filament.sezione.pages.calendario'),
            ],
        ];
    }
}
