<?php

namespace App\Services\Opportunity;

use App\Models\Data\Timezone;
use App\Models\User;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

class AccountOwnerResolver
{
    protected array $accountOwnerCache = [];

    public function __invoke(?string $accountOwnerName): ?User
    {
        if (is_null($accountOwnerName)) {
            return null;
        }

        return $this->accountOwnerCache[$accountOwnerName] ??= with(trim($accountOwnerName), function (string $accountOwnerName): User {

            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return User::query()->where('user_fullname', $accountOwnerName)->first() ?? tap(new User(), function (User $user) use ($accountOwnerName) {
                if (str_contains($accountOwnerName, ' ')) {
                    [$firstName, $lastName] = explode(' ', $accountOwnerName, 2);
                } else {
                    [$firstName, $lastName] = [$accountOwnerName, ''];
                }

                $user->{$user->getKeyName()} = (string)Uuid::generate(4);
                $user->first_name = $firstName;
                $user->last_name = $lastName;
                $user->email = sprintf('%s@easyquote.com', Str::slug($accountOwnerName, '.'));
                $user->timezone_id = Timezone::query()->where('abbr', 'GMT')->value('id');
                $user->team_id = UT_EPD_WW;

                $user->save();
            });

        });
    }
}