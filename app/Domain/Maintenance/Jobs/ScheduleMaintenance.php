<?php

namespace App\Domain\Maintenance\Jobs;

use App\Domain\Maintenance\Events\MaintenanceScheduled as MaintenanceScheduledEvent;
use App\Domain\Maintenance\Notifications\MaintenanceScheduled;
use App\Domain\User\Contracts\UserRepositoryInterface as Users;
use App\Domain\User\Models\User;
use App\Foundation\Mail\Exceptions\MailRateLimitException;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

class ScheduleMaintenance implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public Carbon $startTime;

    public Carbon $endTime;

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

        $startIn = Str::of($startInMinutes)->append(' ', Str::plural('minute', $startInMinutes))->__toString();
        $duration = Str::of($unavailableMinutes)->append(' minutes')->__toString();

        slack()
            ->title('Maintenance')
            ->status(['Maintenance scheduled', 'Start In' => $startIn, 'Duration' => $duration])
            ->image(assetExternal(SN_IMG_MS))
            ->send();

        $users->cursor()
            ->each(function (User $user): void {
                MaintenanceScheduledEvent::dispatch($user, $this->startTime);
            });

        try {
            $users->cursor()
                ->each(function (User $user): void {
                    $user->notify(new MaintenanceScheduled($this->startTime, $this->endTime));
                });
        } catch (MailRateLimitException $e) {
            report($e);
        }
    }
}
