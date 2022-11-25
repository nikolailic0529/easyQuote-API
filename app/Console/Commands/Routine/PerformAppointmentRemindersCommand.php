<?php

namespace App\Console\Commands\Routine;

use App\Services\Appointment\PerformAppointmentReminderService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

class PerformAppointmentRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:perform-appointment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform appointment reminders';

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
    public function handle(PerformAppointmentReminderService $service, LogManager $logManager): int
    {
        $logManager->setDefaultDriver('appointments');

        $service
            ->setLogger($logManager->stack(['stdout', 'appointments']))
            ->process();

        return self::SUCCESS;
    }
}
