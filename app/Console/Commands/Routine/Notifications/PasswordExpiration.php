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

    /** @var \App\Contracts\Repositories\UserRepositoryInterface */
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
        \DB::transaction(function () {
            $this->users->cursor($this->scope())
                ->each(fn (User $user) => $this->serveUser($user));
        });
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

    protected function scope(): Closure
    {
        return function (Builder $query) {
            $beforeDays = ENF_PWD_CHANGE_DAYS - setting('password_expiry_notification');

            $query->whereNull('password_changed_at')
                ->orWhere(function (Builder $query) use ($beforeDays) {
                    $query->whereRaw('datediff(now(), `password_changed_at`) >= ?', [$beforeDays])
                        ->whereRaw('datediff(now(), `password_changed_at`) < ?', ENF_PWD_CHANGE_DAYS);
                });
        };
    }
}
