<?php

namespace Modules\HrmsIntegrationModule\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class HrmsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module services.
     */
    public function boot()
    {
        $this->registerRoutes();
        
        // Load module views if they exist
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'hrms');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Register event listener map
        $this->app->register(HrmsEventServiceProvider::class);
    }

    /**
     * Register the module routes
     */
    protected function registerRoutes()
    {
        // Web routes are automatically registered and namespaced by App\Providers\RouteServiceProvider

        // API routes for HRMS Integration
        Route::middleware(['api'])
            ->prefix('api/hrms')
            ->name('api.hrms.')
            ->namespace('Modules\HrmsIntegrationModule\Controllers\Api')
            ->group(__DIR__ . '/../../routes/api.php');
    }
}
