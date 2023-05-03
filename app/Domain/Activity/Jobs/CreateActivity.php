<?php

namespace App\Domain\Activity\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class CreateActivity implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    protected ActivityContract $activity;

    /**
     * Create a new job instance.
     */
    public function __construct(ActivityContract $activity)
    {
        $this->activity = $activity;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        rescue(
            fn () => $this->activity->saveOrFail(),
            fn () => $this->release()
        );
    }
}
