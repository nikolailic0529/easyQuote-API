<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\MaintenanceScheduled as MaintenanceScheduledEvent;
use App\Notifications\MaintenanceScheduled;
use App\Contracts\Repositories\UserRepositoryInterface as Users;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ScheduleMaintenance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var \Carbon\Carbon */
    protected Carbon $startTime;

    /** @var \Carbon\Carbon */
    protected Carbon $endTime;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Carbon $startTime, Carbon $endTime)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Users $users)
    {
        $startInMinutes = max($this->startTime->diffInMinutes(now()->startOfMinute()), 1);
        $unavailableMinutes = $this->startTime->diffInMinutes($this->endTime);

        $startIn = Str::of($startInMinutes)->append(Str::plural('minute', $startInMinutes))->__toString();
        $duration = Str::of($unavailableMinutes)->append(' minutes')->__toString();

        slack()
            ->title('Maintenance')
            ->status(['Maintenance scheduled', 'Start In' => $startIn, 'Duration' => $duration])
            ->image(assetExternal(SN_IMG_MS))
            ->send();

        $users->cursor()->each(
            function (User $user) {
                MaintenanceScheduledEvent::dispatch($user, $this->startTime);
                $user->notify(new MaintenanceScheduled($this->startTime, $this->endTime));
            }
        );
    }
}
