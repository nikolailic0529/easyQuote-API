<?php

namespace App\Console\Commands\Routine;

use App\Services\Task\ProcessTaskRecurrenceService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class PerformTaskRecurrencesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:perform-task-recurrences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform the due task recurrences';

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
    public function handle(ProcessTaskRecurrenceService $service, LogManager $logManager)
    {
        $logManager->setDefaultDriver('tasks');

        $service->setLogger($logManager->stack(['stdout', 'tasks']))
            ->process();

        return self::SUCCESS;
    }
}
