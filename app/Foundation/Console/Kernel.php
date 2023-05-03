<?php

namespace App\Foundation\Console;

use App\Domain\Appointment\Commands\PerformAppointmentRemindersCommand;
use App\Domain\Task\Commands\PerformTaskRecurrencesCommand;
use App\Domain\Task\Commands\PerformTaskRemindersCommand;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Define the application's command schedule.
     *
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

        /*
         * Logout the users with expired activity.
         */
        $schedule->command('eq:logout-inactive-users')->runInBackground()->everyMinute();

        /*
         * Update address locations.
         */
        $schedule->command('eq:update-address-locations')->runInBackground()->everyFifteenMinutes()
            /*
             * Migrate non-migrated customers to external companies.
             */
            ->after(fn () => $this->call('eq:migrate-customers'))
            /*
             * Migrate submitted quotes assets where assets are not migrated.
             * Mark quotes with migrated assets.
             */
            ->after(fn () => $this->call('eq:migrate-assets'))
            /*
             * Calculate quote totals.
             * Calculate customer totals for each location based on quote totals.
             *
             * @see \App\Domain\Stats\Commands\CalculateStatsCommand
             */
            ->after(fn () => $this->call('eq:calculate-stats'));

        /*
         * Notify user tasks expiration.
         * Once user will be notified notification won't be sent again. [except case when task expiry_date is updated]
         *
         * @see \App\Domain\Task\Commands\NotifyTasksExpirationCommand
         */
        $schedule->command('eq:notify-tasks-expiration')->runInBackground()->everyMinute();

        /*
         * Notifiy user quotes expiration.
         * Once user will be notified notification won't be sent again.
         *
         * @see \App\Domain\Rescue\Commands\NotifyQuotesExpirationCommand
         */
        $schedule->command('eq:notify-quotes-expiration')->runInBackground()->everyMinute();

        /*
         * Notifiy user password expiration.
         * Notification is reiterating every day until user will change the password.
         *
         * @see \App\Domain\User\Commands\NotifyPasswordExpirationCommand
         */
        $schedule->command('eq:notify-password-expiration')->runInBackground()->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->loadCommandsUsingPattern(
            base_path('app/Domain/**/Commands'),
        );

        require base_path('routes/console.php');
    }

    protected function loadCommandsUsingPattern(string ...$paths): void
    {
        $namespace = $this->app->getNamespace();

        foreach ((new Finder())->in($paths)->files() as $command) {
            $command = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
            );

            if (is_subclass_of($command, Command::class) &&
                !(new \ReflectionClass($command))->isAbstract()) {
                Artisan::starting(function ($artisan) use ($command) {
                    $artisan->resolve($command);
                });
            }
        }
    }
}
