<?php

namespace App\Domain\Maintenance\Jobs;

use App\Domain\Maintenance\Contracts\ManagesMaintenanceStatus;
use App\Domain\Maintenance\Events\MaintenanceScheduled;
use App\Domain\Maintenance\Notifications\MaintenanceScheduledNotification;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class DownIntoMaintenanceMode
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly Carbon $startTime,
        private readonly Carbon $endTime,
        private readonly bool $autocomplete = false,
    ) {
    }

    public function handle(ManagesMaintenanceStatus $service): void
    {
        $service->writeMaintenanceData();

        $this->notifyMaintenanceScheduled();

        $job = NotifyMaintenanceStarted::dispatch($this->endTime)
            ->onQueue('long')
            ->delay($this->startTime);

        if ($this->autocomplete) {
            $job->chain([
                (new UpFromMaintenanceMode())->delay($this->endTime)->onQueue('long'),
            ]);
        }
    }

    private function notifyMaintenanceScheduled(): void
    {
        try {
            $users = User::query()
                ->whereNotNull('activated_at')
                ->get();

            $users->each(function (User $user): void {
                MaintenanceScheduled::dispatch($user, $this->startTime);
            });

            Notification::send($users, new MaintenanceScheduledNotification($this->startTime, $this->endTime));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
