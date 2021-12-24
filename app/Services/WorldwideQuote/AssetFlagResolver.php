<?php

namespace App\Services\WorldwideQuote;

use App\DTO\WorldwideQuote\Export\AssetData;
use App\DTO\WorldwideQuote\Export\AssetsGroupData;

class AssetFlagResolver
{
    const GENERATED_SERIAL = 2;
    const CANADA_LOCATION = 4;
    const SPECIFIC_STATES = 8;

    protected readonly array $assets;

    public function __construct(AssetData|AssetsGroupData ...$assets)
    {
        $this->assets = $assets;
    }

    /**
     * Returns bit mask.
     *
     * @return int
     */
    public function __invoke(): int
    {
        $flags = 0;

        if ($this->seeContainGeneratedSerialNumber()) {
            $flags |= self::GENERATED_SERIAL;
        }

        if ($this->seeContainCountryCode('CA')) {
            $flags |= self::CANADA_LOCATION;
        }

        if ($this->seeContainAnyState('California', 'Connecticut', 'Hawaii', 'Maryland', 'Massachusetts', 'Nevada', 'Puerto Rico', 'Rhode Island', 'Tennessee')) {
            $flags |= self::SPECIFIC_STATES;
        }

        return $flags;
    }

    protected function seeContainGeneratedSerialNumber(): bool
    {
        foreach ($this->iterateAssetCollection() as $asset) {
            if ($asset->is_serial_number_generated) {
                return true;
            }
        }

        return false;
    }

    protected function seeContainCountryCode(string $countryCode): bool
    {
        foreach ($this->iterateAssetCollection() as $asset) {
            if ($countryCode === $asset->country_code) {
                return true;
            }
        }

        return false;
    }

    protected function seeContainAnyState(string ...$states): bool
    {
        foreach ($this->iterateAssetCollection() as $asset) {

            foreach ($states as $state) {
                if (strtolower($state) === trim(strtolower($asset->state))) {
                    return true;
                }
            }

        }

        return false;
    }

    private function iterateAssetCollection(): \Generator
    {
        foreach ($this->assets as $asset) {
            if ($asset instanceof AssetData) {
                yield $asset;
            } else {
                yield from $asset->assets;
            }
        }
    }
}