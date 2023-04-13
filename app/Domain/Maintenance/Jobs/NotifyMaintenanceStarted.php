<?php

namespace App\Domain\Maintenance\Jobs;

use App\Domain\Maintenance\Events\MaintenanceStarted;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

final class NotifyMaintenanceStarted implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        private readonly Carbon $time
    ) {
    }

    public function handle(): void
    {
        try {
            User::query()
                ->whereNotNull('activated_at')
                ->lazy()
                ->each(function (User $user): void {
                    MaintenanceStarted::dispatch($user, $this->time);
                });
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
