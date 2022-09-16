<?php

namespace App\Console\Commands\Pipeliner;

use App\Jobs\Pipeliner\QueuedPipelinerDataSync;
use App\Services\Pipeliner\SyncPipelinerDataStatus;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Console\Input\InputArgument;

class PlSyncStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:pl-sync-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush pipeliner sync status';

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
    public function handle(): int
    {
        match ($this->argument('action')) {
            'show' => $this->showStatus(),
            'flush' => $this->flushStatus(),
            default => throw new \InvalidArgumentException("Invalid command action. Supported: show, flush."),
        };

        return self::SUCCESS;
    }

    protected function showStatus(): void
    {
        $status = $this->laravel->make(SyncPipelinerDataStatus::class);

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
    }

    protected function flushStatus(): void
    {
        $this->releaseJob();
        $this->clearStatus();

        $this->info("Pipeliner sync status has been flushed.");
    }

    protected function clearStatus(): void
    {
        $this->laravel->make(SyncPipelinerDataStatus::class)->clear();
    }

    protected function releaseJob(): void
    {
        $command = new QueuedPipelinerDataSync();

        $uniqueId = method_exists($command, 'uniqueId')
            ? $command->uniqueId()
            : ($command->uniqueId ?? '');

        $cache = method_exists($command, 'uniqueVia')
            ? $command->uniqueVia()
            : $this->laravel->make(Cache::class);

        $cache->lock(
            'laravel_unique_job:'.get_class($command).$uniqueId
        )->forceRelease();
    }

    protected function getArguments(): array
    {
        return [
            new InputArgument('action', InputArgument::REQUIRED, 'Command action: flush or show'),
        ];
    }
}
