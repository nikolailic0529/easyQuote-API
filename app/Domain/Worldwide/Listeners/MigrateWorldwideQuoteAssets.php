<?php

namespace App\Domain\Worldwide\Listeners;

use App\Domain\Asset\Services\AssetFlowService;
use App\Domain\Worldwide\Contracts\WithWorldwideQuoteEntity;
use Illuminate\Contracts\Queue\ShouldQueue;

final class MigrateWorldwideQuoteAssets implements ShouldQueue
{
    public function __construct(
        protected readonly AssetFlowService $assetFlowService
    ) {
    }

    public function handle(WithWorldwideQuoteEntity $event): bool
    {
        $quote = $event->getQuote();

        rescue(function () use ($quote): void {
            $this->assetFlowService->migrateAssetsFromWorldwideQuote($quote->activeVersion);
        });

        return true;
    }
}
