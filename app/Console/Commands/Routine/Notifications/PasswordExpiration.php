<?php

namespace App\Console\Commands\Routine\Notifications;

use Illuminate\Console\Command;
use App\Contracts\Repositories\UserRepositoryInterface as Users;
use App\Models\User;
use App\Notifications\PasswordExpiration as PasswordExpirationNotification;
use Illuminate\Database\Eloquent\Builder;
use Closure;

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

    protected Users $users;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Users $users)
    {
        parent::__construct();

        $this->users = $users;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \DB::transaction(
            fn () =>
            $this->users->cursor(static::scope())
                ->each(fn (User $user) => $this->serveUser($user))
        );
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

        return fn (Builder $query) =>
        $query->whereNull('password_changed_at')
            ->orWhere(
                fn (Builder $query) =>
                $query->whereRaw('datediff(now(), `password_changed_at`) >= ?', [$beforeDays])
                    ->whereRaw('datediff(now(), `password_changed_at`) < ?', ENF_PWD_CHANGE_DAYS)
            );
    }
}
