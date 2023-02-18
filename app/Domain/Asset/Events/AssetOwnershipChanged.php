<?php

namespace App\Domain\Asset\Events;

use App\Domain\Asset\Models\Asset;
use Illuminate\Database\Eloquent\Model;

final class AssetOwnershipChanged
{
    public function __construct(
        public readonly Asset $asset,
        public readonly Asset $oldAsset,
        public readonly ?Model $causer,
    ) {
    }
}
