<?php

namespace App\Console;

use App\Console\Commands\Routine\PerformAppointmentRemindersCommand;
use App\Console\Commands\Routine\PerformTaskRecurrencesCommand;
use App\Console\Commands\Routine\PerformTaskRemindersCommand;
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
        $schedule->command(PerformTaskRecurrencesCommand::class)
            ->when(config('task.recurrence.schedule.enabled'))
            ->dailyAt('8:00')
            ->runInBackground()
            ->withoutOverlapping();

        $schedule->command(PerformTaskRemindersCommand::class)
            ->when(config('task.reminder.schedule.enabled'))
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping();

        $schedule->command(PerformAppointmentRemindersCommand::class)
            ->when(config('appointment.reminder.schedule.enabled'))
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping();

        /**
         * Logout the users with expired activity.
         */
        $schedule->command('eq:logout-inactive-users')->runInBackground()->everyMinute();

        /**
         * Update address locations.
         */
        $schedule->command('eq:update-address-locations')->runInBackground()->everyFifteenMinutes()
            /**
             * Migrate non-migrated customers to external companies.
             */
            ->after(fn() => $this->call('eq:migrate-customers'))
            /**
             * Migrate submitted quotes assets where assets are not migrated.
             * Mark quotes with migrated assets.
             */
            ->after(fn() => $this->call('eq:migrate-assets'))
            /**
             * Calculate quote totals.
             * Calculate customer totals for each location based on quote totals.
             *
             * @see \App\Console\Commands\Routine\CalculateStats
             */
            ->after(fn() => $this->call('eq:calculate-stats'));

        /**
         * Notify user tasks expiration.
         * Once user will be notified notification won't be sent again. [except case when task expiry_date is updated]
         *
         * @see \App\Console\Commands\Routine\Notifications\TaskExpiration
         */
        $schedule->command('eq:notify-tasks-expiration')->runInBackground()->everyMinute();

        /**
         * Notifiy user quotes expiration.
         * Once user will be notified notification won't be sent again.
         *
         * @see \App\Console\Commands\Routine\Notifications\QuotesExpiration
         */
        $schedule->command('eq:notify-quotes-expiration')->runInBackground()->everyMinute();

        /**
         * Notifiy user password expiration.
         * Notification is reiterating every day until user will change the password.
         *
         * @see \App\Console\Commands\Routine\Notifications\PasswordExpiration
         */
        $schedule->command('eq:notify-password-expiration')->runInBackground()->daily();
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
