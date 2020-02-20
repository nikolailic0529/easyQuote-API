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
        $chain = [(new StartMaintenance($this->endTime))->delay($this->startTime)];

        if ($this->autoComplete) {
            $chain[] = (new StopMaintenance)->delay($this->endTime);
        }

        ScheduleMaintenance::withChain($chain)->dispatch($this->startTime);
    }
}
