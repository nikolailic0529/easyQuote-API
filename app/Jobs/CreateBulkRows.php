<?php namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;

class CreateBulkRows implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $rowsChunk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $rowsChunk)
    {
        $this->rowsChunk = $rowsChunk;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::connection()->disableQueryLog();
        DB::transaction(function () {
            DB::table('imported_rows')->insert($this->rowsChunk);
        }, 5);
        DB::connection()->enableQueryLog();
    }
}
