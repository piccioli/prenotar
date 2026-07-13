<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Admin\Resources\AuditLogResource;
use App\Filament\Admin\Resources\PrenotazioneResource;
use App\Filament\Admin\Resources\UserResource;
use App\Filament\Admin\Widgets\StatoSistemaWidget;
use App\Filament\Pages\FirstAccessPage;
use App\Http\Middleware\EnsureContactEmail;
use App\Support\Auth\PanelRedirector;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(fn () => redirect(PanelRedirector::loginUrl()))
            ->passwordReset()
            ->colors([
                'primary' => Color::hex('#C77E2A'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('Prenotar — Admin')
            ->brandLogo(asset('images/cai-lombardia-placeholder.svg'))
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Pages\Dashboard::class,
                FirstAccessPage::class,
            ])
            ->navigationItems([
                NavigationItem::make('Horizon')
                    ->url('/horizon')
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-queue-list')
                    ->group('Diagnostica')
                    ->sort(2)
                    ->visible(fn (): bool => auth()->user()?->isAdmin() === true),
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                StatoSistemaWidget::class,
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
                PanelsRenderHook::TOPBAR_END,
                fn (): string => request()->routeIs('filament.admin.pages.dashboard')
                    ? view('filament.admin.widgets.dashboard-status-pill', [
                        'erroriRecenti' => (new StatoSistemaWidget)->getErroriRecenti(),
                    ])->render()
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.components.mobile-bottom-nav', [
                    'items' => self::mobileNavItems(),
                ])->render(),
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
                'active' => request()->routeIs('filament.admin.pages.dashboard'),
            ],
            [
                'label' => 'Utenti',
                'icon' => 'heroicon-o-users',
                'url' => UserResource::getUrl(),
                'active' => request()->routeIs([
                    'filament.admin.resources.users.index',
                    'filament.admin.resources.users.create',
                    'filament.admin.resources.users.edit',
                ]),
            ],
            [
                'label' => 'Prenotazioni',
                'icon' => 'heroicon-o-clipboard-document-list',
                'url' => PrenotazioneResource::getUrl(),
                'active' => request()->routeIs([
                    'filament.admin.resources.prenotaziones.index',
                    'filament.admin.resources.prenotaziones.view',
                ]),
            ],
            [
                'label' => 'Audit log',
                'icon' => 'heroicon-o-document-text',
                'url' => AuditLogResource::getUrl(),
                'active' => request()->routeIs([
                    'filament.admin.resources.audit-logs.index',
                    'filament.admin.resources.audit-logs.view',
                ]),
            ],
        ];
    }
}
