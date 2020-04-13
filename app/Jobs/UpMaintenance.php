<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use App\Facades\Maintenance;

class UpMaintenance
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Carbon $startTime;

    protected Carbon $endTime;

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
        Maintenance::putData();

        ScheduleMaintenance::dispatchNow($this->startTime, $this->endTime);

        $start = StartMaintenance::dispatch($this->endTime)->delay($this->startTime);

        if ($this->autoComplete) {
            $start->chain([(new StopMaintenance)->delay($this->endTime)]);
        }
    }
}
