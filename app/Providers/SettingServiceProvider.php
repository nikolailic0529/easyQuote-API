<?php

namespace App\Providers;

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
    }

    public function provides()
    {
        return [
            SystemSettingRepositoryInterface::class,
            'setting.repository',
        ];
    }
}
