<?php

namespace App\Providers\Filament;

use App\Support\BrandPalette;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            ->login()
            ->brandName('PTK Yayasan Harapan Karya Bangsa')
            // Design system hanya mendefinisikan tema terang; heading Source Serif 4 di tema kustom
            ->darkMode(false)
            ->viteTheme('resources/css/filament/admin/theme.css')
            // Notifikasi "ekspor selesai" berisi tautan unduh berkas XLSX (REQ-13)
            ->databaseNotifications()
            // Warna mengikuti docs/design-system.md §2 (shade 600 = token)
            ->colors([
                'primary' => BrandPalette::make('#14306C', lightest: '#E7ECF6', darkest: '#061540'),
                'info' => BrandPalette::make('#14306C', lightest: '#E7ECF6', darkest: '#061540'),
                'success' => BrandPalette::make('#2F7D4F'),
                'warning' => BrandPalette::make('#B7791F'),
                'danger' => BrandPalette::make('#B42318'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
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
            ]);
    }
}
