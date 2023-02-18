<?php

namespace App\Domain\Settings\Providers;

use App\Domain\Settings\Models\SystemSetting;
use App\Domain\Settings\Policies\SystemSettingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SettingsAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(SystemSetting::class, SystemSettingPolicy::class);
    }
}
