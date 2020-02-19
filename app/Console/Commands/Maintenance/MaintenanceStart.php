<?php

namespace App\Console\Commands\Maintenance;

use Illuminate\Console\Command;
use App\Jobs\UpMaintenance;
use App\Contracts\Repositories\System\BuildRepositoryInterface as Builds;
use Carbon\Carbon;
use Arr, Str;

class MaintenanceStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:maintenance-start {start_time} {end_time} {build_number} {git_tag}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Put the application into maintenance mode';

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
    public function handle(Builds $builds)
    {
        $scheduledMinutes = (int) $this->argument('start_time');
        $start_time = now()->addMinutes($scheduledMinutes);

        $totalMinutes = (int) $this->argument('end_time');
        $end_time = now()->addMinutes($totalMinutes + $scheduledMinutes);

        $buildAttributes = Arr::only($this->arguments(), ['build_number', 'git_tag']);
        $builds->create($buildAttributes + compact('start_time', 'end_time'));

        UpMaintenance::dispatch($start_time, $end_time);

        $this->wait($start_time);

        $this->maintenanceStartedMessage($totalMinutes);
    }

    protected function wait(Carbon $schedule)
    {
        while ($schedule->gt($now = now())) {
            sleep(2);
            $this->remainingTimeMessage($schedule->diffInSeconds($now));
        }
    }

    protected function remainingTimeMessage(int $seconds)
    {
        $unit = Str::plural('second', $seconds);

        $this->warn("Maintenance will start in {$seconds} {$unit} ...");
    }

    protected function maintenanceStartedMessage(int $minutes)
    {
        $unit = Str::plural('minute', $minutes);

        $this->output->write("\n");
        $this->alert("Maintenance started! Estimated time is {$minutes} {$unit}.");
    }
}
