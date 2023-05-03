<?php

namespace App\Domain\User\Facades;

use App\Domain\User\Contracts\UserForm as UserFormContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed getForm($key)
 * @method static mixed updateForm($key, array $attributes)
 *
 * @see \App\Domain\User\Contracts\UserForm
 */
class UserForm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UserFormContract::class;
    }
}
