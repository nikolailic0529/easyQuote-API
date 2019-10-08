<?php namespace App\Jobs;

use App\Models \ {
    User,
    QuoteFile\QuoteFile
};
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Imports\ImportRows;

class ImportExcel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $quoteFile;

    protected $user;

    protected $columns;

    protected $filePath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(QuoteFile $quoteFile, User $user, Collection $columns, string $filePath)
    {
        $this->quoteFile = $quoteFile;
        $this->user = $user;
        $this->columns = $columns;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new ImportRows($this->quoteFile, $this->user, $this->columns))->import($this->filePath);
    }
}
