<?php

namespace App\Domain\Maintenance\Jobs;

use App\Domain\Maintenance\Facades\Maintenance;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpMaintenance
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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

        ScheduleMaintenance::dispatchSync($this->startTime, $this->endTime);

        $start = StartMaintenance::dispatch($this->endTime)
            ->onQueue('long')
            ->delay($this->startTime);

        if ($this->autoComplete) {
            $start->chain([(new StopMaintenance())->delay($this->endTime)->onQueue('long')]);
        }
    }
}
