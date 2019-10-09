<?php namespace App\Jobs;

use App\Imports\ImportCsv;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $quoteFile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(QuoteFile $quoteFile)
    {
        $this->quoteFile = $quoteFile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new ImportCsv($this->quoteFile))->import();
    }
}
