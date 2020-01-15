<?php

namespace App\Jobs;

use App\Models\System\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CreateActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var \App\Models\System\Activity */
    protected $activity;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Activity $activity)
    {
        $this->activity = $activity->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        rescue(function () {
            $this->activity->saveOrFail();
        }, function () {
            $this->release();
        });
    }
}
