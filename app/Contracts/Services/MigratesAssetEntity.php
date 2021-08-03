<?php

namespace App\Contracts\Services;

interface MigratesAssetEntity
{
    const FRESH_MIGRATE = 1;

    public function migrateAssets(int $flags = 0): void;
}