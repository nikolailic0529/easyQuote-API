<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Contracts\Repositories\UserRepositoryInterface as Users;
use App\Events\MaintenanceStarted;
use App\Models\User;
use Carbon\Carbon;

class StartMaintenance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var \Carbon\Carbon */
    protected Carbon $time;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Carbon $time)
    {
        $this->time = $time;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Users $users)
    {
        $users->cursor()->each(fn (User $user) => MaintenanceStarted::dispatch($user, $this->time));
    }
}
