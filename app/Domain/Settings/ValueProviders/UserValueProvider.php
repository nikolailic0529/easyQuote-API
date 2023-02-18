<?php

namespace App\Domain\Settings\ValueProviders;

use App\Domain\User\Models\User;

class UserValueProvider implements ValueProvider
{
    public function __invoke(): array
    {
        return once(static function (): array {
            return User::query()
                ->orderBy('email')
                ->get(['id', 'email'])
                ->map(static function (User $user): array {
                    return [
                        'label' => $user->email,
                        'value' => $user->getKey(),
                    ];
                })
                ->all();
        });
    }
}
