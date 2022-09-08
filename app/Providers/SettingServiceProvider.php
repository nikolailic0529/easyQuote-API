<?php

namespace App\Providers;

use App\Contracts\Repositories\SettingRepository;
use App\Contracts\Repositories\System\SystemSettingRepositoryInterface;
use App\Repositories\System\CachedSettingRepository;
use App\Repositories\System\SystemSettingRepository;
use App\Services\Settings\DynamicSettingsProviders\DynamicSettingsProvider;
use App\Services\Settings\DynamicSettingsProviders\DynamicSettingsProviderCollection;
use App\Services\Settings\DynamicSettingsProviders\RemainingMailLimitProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SystemSettingRepositoryInterface::class, SystemSettingRepository::class);

        $this->app->alias(SystemSettingRepositoryInterface::class, 'setting.repository');

        $this->app->singleton(SettingRepository::class, CachedSettingRepository::class);

        $this->app->tag([
            RemainingMailLimitProvider::class,
        ], DynamicSettingsProvider::class);

        $this->app->bind(DynamicSettingsProviderCollection::class,
            static function (Container $container): DynamicSettingsProviderCollection {
                return new DynamicSettingsProviderCollection(static function () use ($container) {
                    yield from $container->tagged(DynamicSettingsProvider::class);
                });
            });
    }

    public function provides()
    {
        return [
            SystemSettingRepositoryInterface::class,
            'setting.repository',
            SettingRepository::class,
        ];
    }
}
