<?php

namespace App\Domain\Worldwide\Listeners;

use App\Domain\Asset\Services\AssetFlowService;
use App\Domain\Worldwide\Contracts\WithWorldwideQuoteEntity;

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
