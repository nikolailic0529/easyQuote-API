<?php

namespace App\Services\WorldwideQuote\Models;

use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\WorldwideQuoteAsset;

final class ReplicatedVersionData
{
    protected WorldwideQuoteVersion $replicatedVersion;

    /** @var array[] */
    protected array $addressPivots;

    /** @var array[]  */
    protected array $contactPivots;

    /** @var WorldwideQuoteAsset[] */
    protected array $replicatedPackAssets;

    /** @var ReplicatedDistributorQuoteData[] */
    protected array $replicatedDistributorQuotes;

    /**
     * ReplicatedVersionData constructor.
     * @param WorldwideQuoteVersion $replicatedVersion
     * @param array $addressPivots
     * @param array $contactPivots
     * @param WorldwideQuoteAsset[] $replicatedPackAssets
     * @param ReplicatedDistributorQuoteData[] $replicatedDistributorQuotes
     */
    public function __construct(WorldwideQuoteVersion $replicatedVersion,
                                array $addressPivots,
                                array $contactPivots,
                                array $replicatedPackAssets,
                                array $replicatedDistributorQuotes)
    {
        $this->replicatedVersion = $replicatedVersion;
        $this->addressPivots = $addressPivots;
        $this->contactPivots = $contactPivots;
        $this->replicatedPackAssets = $replicatedPackAssets;
        $this->replicatedDistributorQuotes = $replicatedDistributorQuotes;
    }

    /**
     * @return WorldwideQuoteVersion
     */
    public function getReplicatedVersion(): WorldwideQuoteVersion
    {
        return $this->replicatedVersion;
    }

    /**
     * @return WorldwideQuoteAsset[]
     */
    public function getReplicatedPackAssets(): array
    {
        return $this->replicatedPackAssets;
    }

    /**
     * @return ReplicatedDistributorQuoteData[]
     */
    public function getReplicatedDistributorQuotes(): array
    {
        return $this->replicatedDistributorQuotes;
    }

    /**
     * @return array[]
     */
    public function getAddressPivots(): array
    {
        return $this->addressPivots;
    }

    /**
     * @return array[]
     */
    public function getContactPivots(): array
    {
        return $this->contactPivots;
    }
}
