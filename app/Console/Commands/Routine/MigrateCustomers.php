<?php

namespace App\Console\Commands\Routine;

use App\Facades\CustomerFlow;
use Illuminate\Console\Command;
use Throwable;

class MigrateCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:migrate-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate customers to external companies';

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
    public function handle()
    {
        $this->warn(CUSMG_S_01);
        report_logger(['message' => CUSMG_S_01]);

        try {
            CustomerFlow::migrateCustomers($this->output->createProgressBar());
        } catch (Throwable $e) {
            $this->error(CUSMG_ERR_01);
            report_logger(['ErrorCode' => 'QTC_ERR_01'], report_logger()->formatError(CUSMG_ERR_01, $e));

            return false;
        }

        $this->info("\n".CUSMG_F_01);
        report_logger(['message' => CUSMG_F_01]);

        return true;
    }
}
