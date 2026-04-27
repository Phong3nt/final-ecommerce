<?php

namespace App\Providers;

use App\Services\PaymentServiceInterface;
use App\Services\StripePaymentService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentServiceInterface::class, StripePaymentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
