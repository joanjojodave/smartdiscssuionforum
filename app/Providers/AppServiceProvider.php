<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        // Railway (and most PaaS hosts) terminate HTTPS at a proxy and talk
        // to the app over plain HTTP internally. Without this, Laravel
        // generates http:// asset/URL links on an https:// page, which
        // browsers block as mixed content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
