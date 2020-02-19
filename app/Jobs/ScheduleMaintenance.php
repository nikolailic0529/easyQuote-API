<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Contracts\Repositories\UserRepositoryInterface as Users;
use App\Events\MaintenanceScheduled;
use App\Models\User;

class ScheduleMaintenance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var \Carbon\Carbon */
    protected Carbon $schedule;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Carbon $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Users $users)
    {
        $users->cursor()->each(fn (User $user) => MaintenanceScheduled::dispatch($user, $this->schedule));
    }
}
