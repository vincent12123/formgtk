<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Pesan batas percobaan Cek Status (SPEC.md §5.2).
     */
    public const PESAN_TERLALU_BANYAK = 'Terlalu banyak percobaan, coba lagi sebentar lagi.';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Date picker admin: bukan picker bawaan browser, tampil d F Y Indonesia (REQ-14, AGENTS.md §4)
        DatePicker::configureUsing(fn (DatePicker $picker) => $picker
            ->native(false)
            ->displayFormat('d F Y')
            ->locale('id'));

        // Cek Status: maksimal 5 percobaan per menit per IP (REQ-07, NFR-01)
        RateLimiter::for('cek-status', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn (Request $request, array $headers) => response()
                ->view('public.cek-status.create', ['pesanUmum' => self::PESAN_TERLALU_BANYAK], 429, $headers)));
    }
}
