<?php

namespace App\Domain\Asset\Events;

use App\Domain\Asset\Contracts\WithAssetEntity;
use App\Domain\Asset\Models\Asset;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AssetDeleted implements WithAssetEntity
{
    use Dispatchable;
    use SerializesModels;

    private Asset $asset;

    /**
     * Create a new event instance.
     */
    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }
}
