<?php

namespace App\Providers;

use App\Formatters\DateFormatter;
use App\Formatters\Formatter;
use App\Formatters\NumberFormatter;
use App\Services\Formatter\FormatterDelegatorService;
use Illuminate\Support\ServiceProvider;

class FormatterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(FormatterDelegatorService::class)
            ->needs(Formatter::class)
            ->give([
                'date' => DateFormatter::class,
                'number' => NumberFormatter::class,
            ]);

        $this->app->alias(FormatterDelegatorService::class, 'formatter');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
