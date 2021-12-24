<?php

namespace App\Jobs;

use App\Models\{
    Quote\BaseQuote,
    Quote\QuoteVersion,
};
use App\Services\AssetFlowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{
    InteractsWithQueue,
    SerializesModels,
};

class MigrateQuoteAssets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
