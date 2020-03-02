<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\MaintenanceCompleted;
use App\Contracts\Repositories\System\BuildRepositoryInterface as Builds;
use App\Contracts\Repositories\UserRepositoryInterface as Users;
use App\Models\User;
use App\Notifications\MaintenanceFinished;
use Illuminate\Contracts\Queue\ShouldQueue;

class StopMaintenance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Builds $builds)
    {
        $builds->updateLatestOrCreate(['end_time' => now()]);

        MaintenanceCompleted::dispatch();

        app(Users::class)->cursor()->each(fn (User $user) => $user->notify(new MaintenanceFinished));
    }
}
