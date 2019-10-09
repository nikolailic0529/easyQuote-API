<?php namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class CreateBulkColumnsData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $columnsData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $columnsData)
    {
        $this->columnsData = $columnsData;
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
            DB::table('imported_columns')->insert($this->columnsData);

            $processed_rows_ids = array_unique(array_column($this->columnsData, 'imported_row_id'));
            $processed_at = now()->toDateTimeString();

            DB::table('imported_rows')
                ->whereIn('id', $processed_rows_ids)
                ->whereNull('processed_at')
                ->update(compact('processed_at'));
        }, 2);
        DB::connection()->enableQueryLog();
    }
}
