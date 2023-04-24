<?php

namespace App\Domain\User\Commands;

use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class NotifyPasswordExpirationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:notify-password-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify Users to change password';

    public function handle(): int
    {
        $cursor = User::query()
            ->where(self::scope())
            ->lazyById();

        foreach ($cursor as $user) {
            $this->serveUser($user);
        }

        return self::SUCCESS;
    }

    protected function serveUser(User $user): void
    {
        $expiresInDays = config('user.password_expiration.days') - optional($user->password_changed_at)->diffInDays(now()->startOfDay());
        $expirationDate = now()->startOfDay()->addDays($expiresInDays);

        $user->notify(new PasswordExpiringNotification($expirationDate));
    }

    protected static function scope(): \Closure
    {
        $beforeDays = config('user.password_expiration.days') - setting('password_expiry_notification');

        return fn (Builder $query) => $query->whereNull('password_changed_at')
            ->orWhere(
                fn (Builder $query) => $query->whereRaw('datediff(now(), `password_changed_at`) >= ?', [$beforeDays])
                    ->whereRaw('datediff(now(), `password_changed_at`) < ?', config('user.password_expiration.days'))
            );
    }
}
