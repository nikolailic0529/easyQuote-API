<?php

namespace App\Console\Commands\WorldwideQuote;

use App\Contracts\Services\ProcessesWorldwideDistributionState;
use Illuminate\Console\Command;

class ProcessDistribution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:process-ww-distribution {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the WorldwideDistribution';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

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
    public function handle(ProcessesWorldwideDistributionState $processor)
    {
        $processor->processSingleDistributionImport($this->argument('id'));

        return 0;
    }
}
