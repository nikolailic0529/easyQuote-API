<?php

namespace App\Jobs;

use App\Models\QuoteFile\ImportedRow;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateRow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $row;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ImportedRow $row)
    {
        $this->row = $row;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->row->save();
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        logger($exception->message());
    }
}