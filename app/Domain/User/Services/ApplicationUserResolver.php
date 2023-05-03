<?php

namespace App\Domain\User\Services;

use App\Domain\Timezone\Models\Timezone;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionResolverInterface;

class ApplicationUserResolver
{
    public function __construct(
        protected readonly Repository $config,
        protected readonly LockProvider $lockProvider,
        protected readonly ConnectionResolverInterface $connectionResolver,
    ) {
    }

    public function resolve(): User
    {
        return $this->lockProvider->lock(static::class, 10)
            ->block(30, function () {
                $attributes = $this->config->get('user.default', []);

                $user = User::query()->withTrashed()->where('email', $attributes['email'])->first();

                return $user ?? tap(new User(), function (User $user) use ($attributes): void {
                    $user->email = $attributes['email'];
                    $user->activated_at = null;
                    $user->first_name = $attributes['first_name'];
                    $user->last_name = $attributes['last_name'];
                    $user->timezone()->associate(
                        Timezone::query()
                        ->where('abbr', $attributes['timezone_abbr'])
                        ->first()
                    );

                    $this->connectionResolver->connection()
                        ->transaction(static fn () => $user->save());
                });
            });
    }
}
