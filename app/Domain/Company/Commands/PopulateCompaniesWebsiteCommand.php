<?php

namespace App\Domain\Company\Commands;

use App\Domain\Company\Services\PopulateCompanyWebsiteService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class PopulateCompaniesWebsiteCommand extends Command
{
    protected $name = 'eq:populate-companies-website';

    protected $description = 'Populate website to companies';

    public function handle(
        LogManager $logManager,
        PopulateCompanyWebsiteService $service
    ): int {
        $logManager->setDefaultDriver('companies');

        $logger = $logManager->stack(['stdout', 'companies']);

        $service->setLogger($logger)->work();

        return self::SUCCESS;
    }
}
