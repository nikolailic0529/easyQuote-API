<?php

namespace App\Services\WorldwideQuote\Models;

final class ReplicatedAddressesData
{
    /** @var \App\Models\Address[] */
    protected array $addressModels;

    /** @var array[] */
    protected array $addressPivots;

    /**
     * ReplicatedAddressesData constructor.
     * @param array $addressModels
     * @param array $addressPivots
     */
    public function __construct(array $addressModels, array $addressPivots)
    {
        $this->addressModels = $addressModels;
        $this->addressPivots = $addressPivots;
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
