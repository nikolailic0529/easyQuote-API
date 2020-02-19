<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpMaintenance
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var \Carbon\Carbon */
    protected Carbon $startTime;

    /** @var \Carbon\Carbon */
    protected Carbon $endTime;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Carbon $startTime, Carbon $endTime)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ScheduleMaintenance::withChain([
            (new StartMaintenance($this->endTime))->delay($this->startTime)
        ])->dispatch($this->startTime);
    }
}
