<?php

namespace App\Domain\Maintenance\Jobs;

use App\Domain\Maintenance\Events\MaintenanceStarted;
use App\Domain\User\Contracts\UserRepositoryInterface as Users;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class StartMaintenance implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

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
