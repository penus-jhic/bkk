<?php

namespace App\Providers;

use App\View\Composers\AdminAlertsComposer;
use App\View\Composers\MitraAlertsComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AdminAlertsComposer::class);
        $this->app->singleton(MitraAlertsComposer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gagal cepat: login uji coba tidak boleh aktif di luar APP_ENV=local
        if (config('services.auth_service.dev_bypass') && ! $this->app->environment('local')) {
            throw new \RuntimeException('AUTH_DEV_BYPASS=true hanya boleh dipakai saat APP_ENV=local.');
        }

        View::composer(['admin.partials.header', 'admin.partials.sidebar'], AdminAlertsComposer::class);
        View::composer(['mitra.partials.header', 'mitra.partials.sidebar'], MitraAlertsComposer::class);
    }
}
