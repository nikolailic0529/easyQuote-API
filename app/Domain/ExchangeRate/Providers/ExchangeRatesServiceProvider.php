<?php

namespace App\Domain\ExchangeRate\Providers;

use App\Domain\ExchangeRate\Commands\UpdateExchangeRatesCommand;
use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates;
use App\Domain\ExchangeRate\Enum\EventFrequencyEnum;
use App\Domain\ExchangeRate\Repositories\RateFileRepository;
use App\Domain\Settings\DatabaseSettingsStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class ExchangeRatesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ManagesExchangeRates::class, ER_SERVICE_CLASS);

        $this->app->bind(RateFileRepository::class, function (Application $app) {
            $diskName = $this->app['config']->get('exchange-rates.disk');

            $disk = $app->make(Factory::class)->disk($diskName);

            return RateFileRepository::make($disk);
        });

        $this->app->alias(RateFileRepository::class, 'rate-file.repository');

        $this->app->alias(ManagesExchangeRates::class, 'exchange.service');
    }

    public function boot()
    {
        $this->app->resolving(Schedule::class, static function (Schedule $schedule, Container $container) {
            if (!$container[DatabaseSettingsStatus::class]->isEnabled()) {
                return;
            }

            $frequency = EventFrequencyEnum::tryFrom(setting('exchange_rate_update_schedule') ?? 'monthly');

            if ($frequency instanceof EventFrequencyEnum) {
                $event = $schedule
                    ->command(UpdateExchangeRatesCommand::class)
                    ->runInBackground()
                    ->emailOutputOnFailure(setting('failure_report_recipients')->pluck('email')->all());

                $event->{$frequency->value}();
            }
        });
    }

    public function provides()
    {
        return [
            RateFileRepository::class,
            'rate-file.repository',
            ManagesExchangeRates::class,
            'exchange.service',
        ];
    }
}
