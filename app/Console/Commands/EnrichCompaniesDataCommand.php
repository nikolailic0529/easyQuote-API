<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Company\DataEnrichment\CompanyDataEnrichmentService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;
use Symfony\Component\Console\Helper\ProgressBar;

class EnrichCompaniesDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:enrich-companies-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich companies data using 3rd party sources';

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
    public function handle(LogManager $logManager, CompanyDataEnrichmentService $service): int
    {
        $logManager->setDefaultDriver('companies');
        $logger = $logManager->stack(['companies', 'stdout']);

        $service->setLogger($logger)->work();

        return self::SUCCESS;
    }
}
