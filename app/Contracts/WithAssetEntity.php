<?php

namespace App\Contracts;

use App\Models\Asset;

interface WithAssetEntity
{
    public function getAsset(): Asset;
}
