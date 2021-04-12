<?php

namespace App\Services\WorldwideQuote\Models;

use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\WorldwideQuoteAsset;

final class ReplicatedVersionData
{
    protected WorldwideQuoteVersion $replicatedVersion;

    /** @var WorldwideQuoteAsset[] */
    protected array $replicatedPackAssets;

    /** @var ReplicatedDistributorQuoteData[] */
    protected array $replicatedDistributorQuotes;

    /**
     * ReplicatedVersionData constructor.
     * @param WorldwideQuoteVersion $replicatedVersion
     * @param WorldwideQuoteAsset[] $replicatedPackAssets
     * @param ReplicatedDistributorQuoteData[] $replicatedDistributorQuotes
     */
    public function __construct(WorldwideQuoteVersion $replicatedVersion, array $replicatedPackAssets, array $replicatedDistributorQuotes)
    {
        $this->replicatedVersion = $replicatedVersion;
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
}
