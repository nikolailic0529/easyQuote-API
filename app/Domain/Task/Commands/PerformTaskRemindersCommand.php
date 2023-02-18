<?php

namespace App\Domain\Task\Commands;

use App\Domain\Task\Services\PerformTaskReminderService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class PerformTaskRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:perform-task-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform task reminders';

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
    public function handle(PerformTaskReminderService $service, LogManager $logManager): int
    {
        $logManager->setDefaultDriver('tasks');

        $service
            ->setLogger($logManager->stack(['stdout', 'tasks']))
            ->process();

        return self::SUCCESS;
    }
}
