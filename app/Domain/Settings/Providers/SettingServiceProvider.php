<?php

namespace App\Domain\Settings\Providers;

use App\Domain\Settings\Contracts\SettingRepository;
use App\Domain\Settings\Contracts\SystemSettingRepositoryInterface;
use App\Domain\Settings\DatabaseSettingsStatus;
use App\Domain\Settings\DynamicSettings\DynamicSettingsProvider;
use App\Domain\Settings\DynamicSettings\DynamicSettingsProviderCollection;
use App\Domain\Settings\DynamicSettings\RemainingMailLimitProvider;
use App\Domain\Settings\Models\SystemSetting;
use App\Domain\Settings\Observers\SystemSettingObserver;
use App\Domain\Settings\Repositories\CachedSettingRepository;
use App\Domain\Settings\Repositories\SystemSettingRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SystemSettingRepositoryInterface::class, SystemSettingRepository::class);

        $this->app->alias(SystemSettingRepositoryInterface::class, 'setting.repository');

        $this->app->singleton(SettingRepository::class, CachedSettingRepository::class);

        $this->app->singleton(DatabaseSettingsStatus::class);

        $this->app->when(DatabaseSettingsStatus::class)
            ->needs(Builder::class)
            ->give(static function (Container $container): Builder {
                return $container['db.connection']->getSchemaBuilder();
            });

        $this->app->alias(DatabaseSettingsStatus::class, 'settings.status');

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

    public function boot(): void
    {
        SystemSetting::observe(SystemSettingObserver::class);
    }

    public function provides(): array
    {
        return [
            SystemSettingRepositoryInterface::class,
            'setting.repository',
            SettingRepository::class,
        ];
    }
}
