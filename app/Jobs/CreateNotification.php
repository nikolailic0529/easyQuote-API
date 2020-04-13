<?php

namespace App\Jobs;

use App\Models\System\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CreateNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected Notification $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        rescue(
            fn () => $this->notification->saveOrFail(),
            fn () => $this->release()
        );
    }
}
