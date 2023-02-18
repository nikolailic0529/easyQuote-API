<?php

namespace App\Domain\Pipeliner\Commands;

use App\Domain\Pipeliner\Services\SyncPipelinerDataStatus;
use Illuminate\Console\Command;

class TerminatePlSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:terminate-pl-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate pipeliner sync';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(SyncPipelinerDataStatus $status): int
    {
        $released = $status->forceRelease();

        if ($released) {
            $this->info('Sync status: released.');
        } else {
            $this->warn('Sync status: could not release.');
        }

        return self::SUCCESS;
    }
}
