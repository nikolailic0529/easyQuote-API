<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Models;

final class ReplicatedAddressesData
{
    /**
     * ReplicatedAddressesData constructor.
     */
    public function __construct(protected array $addressModels, protected array $addressPivots)
    {
    }

    /**
     * @return \App\Domain\Address\Models\Address[]
     */
    public function getAddressModels(): array
    {
        return $this->addressModels;
    }

    /**
     * @return array[]
     */
    public function getAddressPivots(): array
    {
        return $this->addressPivots;
    }
}
