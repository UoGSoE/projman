<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
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
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

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

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
