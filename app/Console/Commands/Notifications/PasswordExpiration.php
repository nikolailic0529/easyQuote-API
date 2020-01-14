<?php

namespace App\Console\Commands\Notifications;

use Illuminate\Console\Command;
use App\Contracts\Repositories\UserRepositoryInterface as User;
use App\Models\User as UserModel;
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
    protected $user;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        parent::__construct();

        $this->user = $user;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \DB::transaction(function () {
            $this->user->cursor($this->scope())
                ->each(function ($user) {
                    $this->serveUser($user);
                });
        });
    }

    protected function serveUser(UserModel $user): void
    {
        $expiresInDays = ENF_PWD_CHANGE_DAYS - optional($user->password_changed_at)->diffInDays(now());
        $expires_at = now()->addDays($expiresInDays)->format('d M');

        notification()
            ->for($user)
            ->message(__(PWDE_01, compact('expires_at')))
            ->subject($user)
            ->url(ui_route('users.profile'))
            ->priority(3)
            ->store();
    }

    protected function scope(): Closure
    {
        return function (Builder $query) {
            $beforeDays = ENF_PWD_CHANGE_DAYS - setting('password_expiry_notification');

            $query->whereNull('password_changed_at')
                ->orWhereRaw('datediff(now(), `password_changed_at`) >= ?', [$beforeDays]);
        };
    }
}
