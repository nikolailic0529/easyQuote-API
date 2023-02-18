<?php

namespace App\Domain\Maintenance\Jobs;

use App\Domain\Build\Contracts\BuildRepositoryInterface as Builds;
use App\Domain\Maintenance\Events\MaintenanceCompleted;
use App\Domain\Maintenance\Notifications\MaintenanceFinished;
use App\Domain\User\Contracts\{UserRepositoryInterface as Users};
use App\Domain\User\Models\User;
use App\Foundation\Mail\Exceptions\MailRateLimitException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class StopMaintenance implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

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
            app(Users::class)->cursor()->each(static fn (User $user) => $user->notify(new MaintenanceFinished()));
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
