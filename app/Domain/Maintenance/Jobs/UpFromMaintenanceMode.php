<?php

namespace App\Domain\Maintenance\Jobs;

use App\Domain\Build\Contracts\BuildRepositoryInterface;
use App\Domain\Maintenance\Events\MaintenanceCompleted;
use App\Domain\Maintenance\Notifications\MaintenanceFinishedNotification;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class UpFromMaintenanceMode implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function handle(BuildRepositoryInterface $buildRepository): void
    {
        $buildRepository->updateLastOrCreate(['end_time' => now()]);

        MaintenanceCompleted::dispatch();

        try {
            $users = User::query()
                ->whereNotNull('activated_at')
                ->get();

            Notification::send($users, new MaintenanceFinishedNotification());
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
