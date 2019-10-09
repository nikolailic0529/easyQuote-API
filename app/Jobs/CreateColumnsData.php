<?php

namespace App\Jobs;

use App\Models\QuoteFile\ImportedRow;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;
use Exception;
use Webpatser\Uuid\Uuid;

class CreateColumnsData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $row;

    protected $columnsData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ImportedRow $row, Collection $columnsData)
    {
        $this->row = $row;
        $this->columnsData = $columnsData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->row->save();
        $this->row->columnsData()->saveMany($this->columnsData);
        $this->row->markAsProcessed();
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        logger($exception->getMessage());
    }
}
