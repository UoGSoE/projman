<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
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
        if (config('proxy.https')) {
            Http::globalOptions([
                'proxy' => [
                    'https' => config('proxy.https'),
                    'http' => config('proxy.http', config('proxy.https')),
                    'no' => config('proxy.no', []),
                ],
            ]);
        }

        // Disabling Lazy Loading to find N+1 problems
        Model::preventLazyLoading(! app()->isProduction());

        Blade::if('admin', function () {
            return auth()->check() && auth()->user()->isAdmin();
        });
    }
}
