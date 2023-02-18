<?php

namespace App\Domain\Asset\Contracts;

use App\Domain\Asset\Models\Asset;

interface WithAssetEntity
{
    public function getAsset(): Asset;
}
