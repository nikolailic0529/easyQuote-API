<?php

namespace App\Providers;

use App\Contracts\Repositories\SettingRepository;
use App\Repositories\System\CachedSettingRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\System\SystemSettingRepositoryInterface;
use App\Repositories\System\SystemSettingRepository;

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
