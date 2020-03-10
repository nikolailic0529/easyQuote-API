<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class UpMaintenance
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var \Carbon\Carbon */
    protected Carbon $startTime;

    /** @var \Carbon\Carbon */
    protected Carbon $endTime;

    /** @var boolean */
    protected bool $autoComplete = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Carbon $startTime, Carbon $endTime, bool $autoComplete = false)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->autoComplete = $autoComplete;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->putMaintenanceData();

        ScheduleMaintenance::dispatchNow($this->startTime, $this->endTime);

        $start = StartMaintenance::dispatch($this->endTime)->delay($this->startTime);

        if ($this->autoComplete) {
            $start->chain((new StopMaintenance)->delay($this->endTime));
        }
    }

    protected function putMaintenanceData()
    {
        $content = [
            'start_time'    => $this->startTime->toISOString(),
            'end_time'      => $this->endTime->toISOString(),
            'created_at'    => now()->toISOString()
        ];

        $path = ui_path('maintenance/maintenance_data.json');

        File::ensureDirectoryExists(ui_path('maintenance'));

        rescue(fn () => File::put($path, json_encode($content, JSON_PRETTY_PRINT)));
    }
}
