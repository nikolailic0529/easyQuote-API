<?php

namespace App\Domain\Formatting\Providers;

use App\Domain\Formatting\Formatters\DateFormatter;
use App\Domain\Formatting\Formatters\DateTimeFormatter;
use App\Domain\Formatting\Formatters\Formatter;
use App\Domain\Formatting\Formatters\NumberFormatter;
use App\Domain\Formatting\Services\FormatterDelegatorService;
use Illuminate\Support\ServiceProvider;

class FormatterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->when(FormatterDelegatorService::class)
            ->needs(Formatter::class)
            ->give([
                'date' => DateFormatter::class,
                'date_time' => DateTimeFormatter::class,
                'number' => NumberFormatter::class,
            ]);

        $this->app->alias(FormatterDelegatorService::class, 'formatter');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }
}
