<?php

namespace App\Console\Commands\Routine\Notifications;

use App\Models\User;
use App\Notifications\PasswordExpiration as PasswordExpirationNotification;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class PasswordExpiration extends Command
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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
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
        $expiresInDays = ENF_PWD_CHANGE_DAYS - optional($user->password_changed_at)->diffInDays(now()->startOfDay());
        $expirationDate = now()->startOfDay()->addDays($expiresInDays);
        $expires_at = $expirationDate->format('d M Y');

        notification()
            ->for($user)
            ->message(__(PWDE_01, compact('expires_at')))
            ->subject($user)
            ->url(ui_route('users.profile'))
            ->priority(3)
            ->store();

        $user->notify(new PasswordExpirationNotification($expirationDate));
    }

    protected static function scope(): Closure
    {
        $beforeDays = ENF_PWD_CHANGE_DAYS - setting('password_expiry_notification');

        return fn(Builder $query) => $query->whereNull('password_changed_at')
            ->orWhere(
                fn(Builder $query) => $query->whereRaw('datediff(now(), `password_changed_at`) >= ?', [$beforeDays])
                    ->whereRaw('datediff(now(), `password_changed_at`) < ?', ENF_PWD_CHANGE_DAYS)
            );
    }
}
