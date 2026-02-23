<?php

namespace App\Providers;

use App\Services\InventoryService;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * SOA: Bind each service as a singleton in the container.
     * This enables constructor injection throughout the application
     * and ensures each service is instantiated only once per request.
     */
    public function register(): void
    {
        $this->app->singleton(InventoryService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(OrderService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
