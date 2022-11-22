<?php

namespace App\Services\Opportunity;

use App\Models\Data\Timezone;
use App\Models\User;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;

class UserResolver
{
    const EMAIL_DOMAIN = 'easyquote.com';
    const TIMEZONE_ABBR = 'UTC';
    const TEAM_ID = UT_EPD_WW;

    public function __construct(
        protected readonly LockProvider $lockProvider,
    ) {
    }

    public function __invoke(?string $name): ?User
    {
        if (is_null($name)) {
            return null;
        }

        $name = trim($name);

        return once(function () use ($name): User {
            return $this->lockProvider->lock(static::class.$name, 30)
                ->block(30, function () use ($name): User {
                    $user = $this->tryFindUser($name);

                    return $user ?? $this->createUser($name);
                });
        });
    }

    private function createUser(string $name): User
    {
        return tap(new User(), function (User $user) use ($name): void {
            $split = static::splitName($name);

            $user->first_name = $split['first_name'];
            $user->last_name = $split['last_name'];
            $user->email = $this->generateEmail($name);
            $user->timezone_id = Timezone::query()->where('abbr', self::TIMEZONE_ABBR)->value('id');
            $user->team_id = self::TEAM_ID;

            $user->save();
        });
    }

    private function tryFindUser(string $name): ?User
    {
        return User::query()
            ->orderByDesc('is_active')
            ->where('user_fullname', $name)->firstOr(callback: function () use ($name): ?User {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return User::query()
                ->where('email', $this->generateEmail($name))
                ->orderByDesc('is_active')
                ->first();
        });
    }

    private function generateEmail(string $name): string
    {
        return sprintf('%s@%s', Str::slug($name, '.'), self::EMAIL_DOMAIN);
    }

    #[ArrayShape(['first_name' => 'string', 'last_name' => 'string'])]
    private static function splitName(string $name): array
    {
        if (str_contains($name, ' ')) {
            [$firstName, $lastName] = explode(' ', $name, 2);
        } else {
            [$firstName, $lastName] = [$name, ''];
        }

        return ['first_name' => $firstName, 'last_name' => $lastName];
    }
}