<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Usar Bootstrap 5 para la paginación
        Paginator::useBootstrapFive();

        // Forzar HTTPS en producción (opcional)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}