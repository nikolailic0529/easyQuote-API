<?php

namespace App\Domain\Worldwide\Commands;

use App\Domain\Worldwide\Services\Opportunity\ValidateOpportunityService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class ValidateOpportunitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:validate-opportunities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle(ValidateOpportunityService $service): int
    {
        $this->withProgressBar(0, static function (ProgressBar $bar) use ($service): void {
            $service->work(progressCallback: static function () use ($bar): void {
                $bar->advance();
            });
        });

        $this->output->newLine();

        return self::SUCCESS;
    }
}
