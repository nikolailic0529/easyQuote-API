<?php

namespace App\Domain\Rescue\Jobs;

use App\Domain\Asset\Services\AssetFlowService;
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Rescue\Models\QuoteVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MigrateQuoteAssets implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected BaseQuote $quote;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BaseQuote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AssetFlowService $service)
    {
        $quote = $this->quote;

        if ($quote instanceof QuoteVersion) {
            $quote = $quote->quote;
        }

        $service->migrateAssetsFromRescueQuote($quote);
    }
}
