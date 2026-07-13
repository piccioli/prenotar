<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Gr\Pages\CalendarioPage;
use App\Filament\Gr\Pages\ImpostazioniPage;
use App\Filament\Gr\Resources\PrenotazioneResource;
use App\Filament\Gr\Resources\PrenotazioneResource\Pages\ViewPrenotazione;
use App\Filament\Gr\Widgets\PrenotazioniDaApprovareWidget;
use App\Filament\Pages\FirstAccessPage;
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

class GrPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('gr')
            ->path('gr')
            ->login(fn () => redirect(PanelRedirector::loginUrl()))
            ->passwordReset()
            ->colors([
                'primary' => Color::hex('#1D574B'),
            ])
            ->viteTheme('resources/css/filament/gr/theme.css')
            ->brandName('Prenotar — GR Lombardia')
            ->brandLogo(asset('images/cai-lombardia-placeholder.svg'))
            ->discoverResources(in: app_path('Filament/Gr/Resources'), for: 'App\\Filament\\Gr\\Resources')
            ->discoverPages(in: app_path('Filament/Gr/Pages'), for: 'App\\Filament\\Gr\\Pages')
            ->pages([
                Pages\Dashboard::class,
                FirstAccessPage::class,
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make(),
            ])
            ->discoverWidgets(in: app_path('Filament/Gr/Widgets'), for: 'App\\Filament\\Gr\\Widgets')
            ->widgets([
                PrenotazioniDaApprovareWidget::class,
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
                PanelsRenderHook::PAGE_START,
                fn (): string => view('filament.gr.resources.prenotazione-resource.pages.dettaglio-mobile-topbar', [
                    'backUrl' => PrenotazioneResource::getUrl('index'),
                ])->render(),
                scopes: [ViewPrenotazione::class],
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
                'active' => request()->routeIs('filament.gr.pages.dashboard'),
            ],
            [
                'label' => 'Prenotazioni',
                'icon' => 'heroicon-o-clipboard-document-list',
                'url' => PrenotazioneResource::getUrl(),
                'active' => request()->routeIs([
                    'filament.gr.resources.prenotaziones.index',
                    'filament.gr.resources.prenotaziones.view',
                ]),
            ],
            [
                'label' => 'Calendario',
                'icon' => 'heroicon-o-calendar-days',
                'url' => CalendarioPage::getUrl(),
                'active' => request()->routeIs('filament.gr.pages.calendario'),
            ],
            [
                'label' => 'Impostazioni',
                'icon' => 'heroicon-o-cog-6-tooth',
                'url' => ImpostazioniPage::getUrl(),
                'active' => request()->routeIs('filament.gr.pages.impostazioni-page'),
            ],
        ];
    }
}
