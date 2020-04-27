<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('eq:calculate-quotes')->runInBackground()->everyFifteenMinutes();
        $schedule->command('eq:notify-tasks-expiration')->runInBackground()->everyMinute();
        $schedule->command('eq:notify-quotes-expiration')->runInBackground()->everyMinute();
        $schedule->command('eq:notify-password-expiration')->runInBackground()->daily();
        $schedule->command('eq:update-exchange-rates', ['--force' => true])->runInBackground()->{setting('exchange_rate_update_schedule')}();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
