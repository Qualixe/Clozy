<?php

namespace Webkul\Shop\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;
use Webkul\Shop\Http\Middleware\AuthenticateCustomer;
use Webkul\Shop\Http\Middleware\CacheResponse;
use Webkul\Shop\Http\Middleware\Currency;
use Webkul\Shop\Http\Middleware\Locale;
use Webkul\Shop\Http\Middleware\Theme;

class ShopServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        $router->middlewareGroup('shop', [
            Theme::class,
            Locale::class,
            Currency::class,
        ]);

        $router->aliasMiddleware('theme', Theme::class);
        $router->aliasMiddleware('locale', Locale::class);
        $router->aliasMiddleware('currency', Currency::class);
        $router->aliasMiddleware('cache.response', CacheResponse::class);
        $router->aliasMiddleware('customer', AuthenticateCustomer::class);

        RateLimiter::for('track-order-lookup', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('checkout-otp-send', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('checkout-otp-verify', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Route::middleware(['web', 'shop', PreventRequestsDuringMaintenance::class])->group(__DIR__.'/../Routes/web.php');
        Route::middleware(['web', 'shop', PreventRequestsDuringMaintenance::class])->group(__DIR__.'/../Routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'shop');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'shop');

        Paginator::defaultView('shop::partials.pagination');
        Paginator::defaultSimpleView('shop::partials.pagination');

        Blade::anonymousComponentPath(__DIR__.'/../Resources/views/components', 'shop');

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/menu.php',
            'menu.customer'
        );
    }
}
