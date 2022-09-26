<?php

namespace App\Console\Commands\Pipeliner;

use App\Services\Pipeliner\SyncPipelinerDataStatus;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class ReleasePlSyncStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:release-pl-sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release pipeliner sync status';

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
        $released = $this->option('force')
            ? $status->forceRelease()
            : $status->release();

        if ($released) {
            $this->info('Sync status: released.');
        } else {
            $this->warn('Sync status: could not release.');
        }

        return self::SUCCESS;
    }

    protected function getOptions(): array
    {
        return [
            new InputOption('--force', mode: InputOption::VALUE_NONE, description: 'Force release status'),
        ];
    }
}
