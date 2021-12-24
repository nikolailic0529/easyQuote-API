<?php

namespace App\Services\WorldwideQuote\Models;

final class ReplicatedAddressesData
{
    /**
     * ReplicatedAddressesData constructor.
     * @param array $addressModels
     * @param array $addressPivots
     */
    public function __construct(protected array $addressModels, protected array $addressPivots)
    {
    }

    /**
     * @return \App\Models\Address[]
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
