<?php

namespace App\Jobs;

use App\Services\Mail\Exceptions\MailRateLimitException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Contracts\Repositories\{
    UserRepositoryInterface as Users,
    System\BuildRepositoryInterface as Builds
};
use App\Models\User;
use App\Events\MaintenanceCompleted;
use App\Notifications\MaintenanceFinished;

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
        $builds->updateLastOrCreate(['end_time' => now()]);

        MaintenanceCompleted::dispatch();

        try {
            app(Users::class)->cursor()->each(static fn (User $user) => $user->notify(new MaintenanceFinished));
        } catch (MailRateLimitException $e) {
            report($e);
        }

        slack()
            ->title('Maintenance')
            ->status(['Maintenance finished', 'Time' => now()->format(config('date.format_time'))])
            ->image(assetExternal(SN_IMG_MF))
            ->send();
    }
}
