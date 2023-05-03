<?php

namespace App\Domain\Pipeliner\Commands;

use App\Domain\Pipeliner\Services\SyncPipelinerDataStatus;
use Illuminate\Console\Command;

class ShowPlSyncStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:show-pl-sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show pipeliner sync status';

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
        $this->output->horizontalTable([
            'Running',
            'Progress',
            'Total',
            'Processed',
        ], [
            [
                $status->running() ? 'true' : 'false',
                $status->progress(),
                $status->total(),
                $status->processed(),
            ],
        ]);

        return self::SUCCESS;
    }
}
