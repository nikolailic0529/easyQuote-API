<?php

namespace App\Jobs;

use App\Models\System\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class CreateActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected ActivityContract $activity;

    /**
     * Create a new job instance.
     *
     * @param ActivityContract $activity
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
