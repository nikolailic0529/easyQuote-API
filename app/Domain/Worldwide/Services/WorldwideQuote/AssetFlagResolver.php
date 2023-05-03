<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote;

use App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetsGroupData;

class AssetFlagResolver
{
    const GEN_SN = 2;
    const CA_LOC = 4;
    const US_LOC = 8;

    protected readonly array $assets;

    public function __construct(AssetData|AssetsGroupData ...$assets)
    {
        $this->assets = $assets;
    }

    /**
     * Returns bit mask.
     */
    public function __invoke(): int
    {
        $flags = 0;

        if ($this->seeContainGeneratedSerialNumber()) {
            $flags |= self::GEN_SN;
        }

        if ($this->seeContainCountryCode('CA')) {
            $flags |= self::CA_LOC;
        }

        if ($this->seeContainCountryCode('US')) {
            $flags |= self::US_LOC;
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
