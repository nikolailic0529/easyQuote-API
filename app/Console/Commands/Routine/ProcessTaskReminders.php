<?php

namespace App\Console\Commands\Routine;

use App\Services\Task\ProcessTaskReminderService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class ProcessTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:process-task-reminders';

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
     *
     * @return mixed
     */
    public function handle(ProcessTaskReminderService $service, LogManager $logManager): int
    {
        $service
            ->setLogger($logManager->stack(['stdout', 'tasks']))
            ->process();

        return self::SUCCESS;
    }
}
