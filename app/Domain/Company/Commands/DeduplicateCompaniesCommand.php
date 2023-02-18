<?php

namespace App\Domain\Company\Commands;

use App\Domain\Company\Services\DeduplicateCompaniesService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;
use Symfony\Component\Console\Input\InputOption;

class DeduplicateCompaniesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:deduplicate-companies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge duplicated companies';

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
    public function handle(DeduplicateCompaniesService $service,
                           LogManager $logManager): int
    {
        $flags = 0;

        if ($this->option('dry-run')) {
            $flags |= DeduplicateCompaniesService::DRY_RUN;
        }

        $service
            ->setLogger($logManager->stack(['stdout', 'daily']))
            ->work($flags);

        return self::SUCCESS;
    }

    protected function getOptions(): array
    {
        return [
            new InputOption(name: 'dry-run', mode: InputOption::VALUE_NONE, description: "Don't update/delete the records."),
        ];
    }
}
