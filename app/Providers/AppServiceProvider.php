<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SiteScanner;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteScanner::class, function ($app) {
            return new SiteScanner();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
