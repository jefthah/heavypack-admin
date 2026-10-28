<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureActiveAdmin;
use Filament\Auth\Pages\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Website orange (#ff6600) at 500 and its hover tone (#e65c00) at 600, as in the public site.
     *
     * @var array<int, string>
     */
    private const BRAND_ORANGE = [
        50 => '#fff6ee',
        100 => '#ffead6',
        200 => '#ffd0a8',
        300 => '#ffb071',
        400 => '#ff8a36',
        500 => '#ff6600',
        600 => '#e65c00',
        700 => '#bf4c00',
        800 => '#983d05',
        900 => '#7a330b',
        950 => '#421804',
    ];

    /**
     * Neutral greys from the public site: canvas #f5f5f7, muted #6e6e73, ink #242424, graphite #181818.
     *
     * @var array<int, string>
     */
    private const BRAND_GRAY = [
        50 => '#f9f9fa',
        100 => '#f5f5f7',
        200 => '#e8e8ed',
        300 => '#d2d2d7',
        400 => '#a1a1a6',
        500 => '#737378',
        600 => '#5c5c61',
        700 => '#48484c',
        800 => '#2e2e30',
        900 => '#242424',
        950 => '#181818',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->databaseTransactions()
            ->profile(isSimple: false)
            ->brandName('HeavyPack Admin')
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('2rem')
            ->favicon(fn () => asset('images/heavypack-favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->maxContentWidth(Width::Full)
            ->navigationGroups(['Produk', 'Konten', 'Media & dokumen', 'Pesan masuk', 'Pengaturan'])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('filament.hooks.styles'))
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn () => view('filament.environment'))
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, fn () => view('filament.login-footer'), scopes: Login::class)
            ->colors([
                'primary' => self::BRAND_ORANGE,
                'gray' => self::BRAND_GRAY,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureActiveAdmin::class,
            ]);
    }
}
