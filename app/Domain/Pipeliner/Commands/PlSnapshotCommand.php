<?php

namespace App\Domain\Pipeliner\Commands;

use App\Domain\Pipeliner\Services\PipelinerSnapshotService;
use Illuminate\Console\Command;

class PlSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:pl-snapshot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create snapshot of Pipeliner data';

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
     */
    public function handle(PipelinerSnapshotService $service): int
    {
        $bar = $this->output->createProgressBar();

        $snapshot = $service->create(static function () use ($bar): void {
            $bar->advance();
        });

        $this->newLine();
        $this->info("Snapshot created: {$snapshot->getKey()}");

        $bar->finish();

        return self::SUCCESS;
    }
}
