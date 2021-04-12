<?php

namespace App\Events\Asset;

use App\Contracts\WithAssetEntity;
use App\Models\Asset;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AssetUpdated implements WithAssetEntity
{
    use Dispatchable, SerializesModels;

    private Asset $asset;

    /**
     * Create a new event instance.
     *
     * @param Asset $asset
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
