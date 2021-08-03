<?php

namespace App\Console\Commands\Routine;

use App\Contracts\Services\MigratesAssetEntity;
use App\Contracts\WithLogger;
use App\Contracts\WithOutput;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class MigrateAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:migrate-assets {--fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate quote assets to respective table';

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
    public function handle(MigratesAssetEntity $service, LogManager $logManager)
    {
        if ($service instanceof WithLogger) {
            $service->setLogger(
                $logManager->stack(['daily', 'stdout'])
            );
        }

        $service->migrateAssets($this->parseCommandFlags());

        return Command::SUCCESS;
    }

    protected function parseCommandFlags(): int
    {
        $flags = 0;

        if ($this->option('fresh')) {
            $flags |= MigratesAssetEntity::FRESH_MIGRATE;
        }

        return $flags;
    }
}
