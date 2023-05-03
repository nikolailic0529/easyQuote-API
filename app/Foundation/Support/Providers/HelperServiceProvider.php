<?php

namespace App\Foundation\Support\Providers;

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        require_once base_path('bootstrap/helpers.php');
    }
}
