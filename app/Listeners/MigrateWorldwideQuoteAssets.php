<?php

namespace App\Listeners;

use App\Contracts\WithWorldwideQuoteEntity;
use App\Services\AssetFlowService;

class MigrateWorldwideQuoteAssets
{
    public function __construct(protected AssetFlowService $assetFlowService)
    {
    }

    public function handle(WithWorldwideQuoteEntity $event): bool
    {
        $quote = $event->getQuote();

        $this->assetFlowService->migrateAssetsFromWorldwideQuote($quote->activeVersion);

        return true;
    }
}
