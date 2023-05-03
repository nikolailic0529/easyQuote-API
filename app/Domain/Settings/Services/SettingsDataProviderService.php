<?php

namespace App\Domain\Settings\Services;

use App\Domain\Settings\DynamicSettings\DynamicSettingsProvider;
use App\Domain\Settings\DynamicSettings\DynamicSettingsProviderCollection;
use App\Domain\Settings\Models\SystemSetting;
use App\Domain\Settings\ValueProviders\ValueProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Collection;

class SettingsDataProviderService
{
    public function __construct(
        protected readonly Repository $config,
        protected readonly DynamicSettingsProviderCollection $dynamicSettings,
    ) {
    }

    public function hydratePossibleValuesOfSettings(Collection $settings): Collection
    {
        return $settings->each(function (SystemSetting $setting): void {
            $setting->possible_values = $this->resolvePossibleValuesForSettingsProperty($setting);
        });
    }

    public function resolvePossibleValuesForSettingsProperty(SystemSetting $property): ?array
    {
        return once(function () use ($property): ?array {
            $provider = $this->resolveValueProviderForSettingsProperty($property);

            return $provider?->__invoke();
        });
    }

    protected function resolveValueProviderForSettingsProperty(SystemSetting $property): ?ValueProvider
    {
        $providers = $this->config->get('settings.value_providers', []);

        if (array_key_exists($property->key, $providers)) {
            $providerConfig = $providers[$property->key];

            return app()->make($providerConfig['provider'], $providerConfig['with'] ?? []);
        }

        return null;
    }

    public function getAdditionalSettingsData(): Collection
    {
        return $this->dynamicSettings
            ->map(static function (DynamicSettingsProvider $provider): SystemSetting {
                return $provider->__invoke();
            })
            ->pipe(static function (DynamicSettingsProviderCollection $collection) {
                return new Collection($collection);
            });
    }
}
