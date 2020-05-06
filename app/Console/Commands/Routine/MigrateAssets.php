<?php

namespace App\Console\Commands\Routine;

use App\Services\AssetService;
use Illuminate\Console\Command;
use Throwable;

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
    public function handle(AssetService $service)
    {
        $this->warn(ASSET_MGS_01);
        report_logger(['message' => ASSET_MGS_01]);

        try {
            $service->migrateAssets((bool) $this->option('fresh'), $this->output->createProgressBar());
        } catch (Throwable $e) {
            $this->error(ASSET_MGERR_02);
            report_logger(['ErrorCode' => 'ASSET_MGERR_02'], report_logger()->formatError(ASSET_MGERR_02, $e));

            return false;
        }

        $this->info("\n".ASSET_MGF_01);
        report_logger(['message' => ASSET_MGF_01]);

        return true;
    }
}
