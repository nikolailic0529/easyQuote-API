<?php

namespace App\Foundation\View\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::if('textoverflow', function ($value, int $max, $when = true) {
            return $when && (bool) preg_match("~\b(\w+){{$max},}\b~", $value);
        });
    }
}
