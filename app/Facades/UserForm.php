<?php

namespace App\Facades;

use App\Contracts\Repositories\UserForm as UserFormContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed getForm($key)
 * @method static mixed updateForm($key, array $attributes)
 *
 * @see \App\Contracts\Repositories\UserForm
 */
class UserForm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UserFormContract::class;
    }
}
