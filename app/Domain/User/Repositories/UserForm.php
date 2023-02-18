<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Contracts\UserForm as Contract;
use App\Domain\User\Models\UserForm as Model;

class UserForm implements Contract
{
    protected Model $userForm;

    public function __construct(Model $userForm)
    {
        $this->userForm = $userForm;
    }

    public function getForm($key)
    {
        $key = static::resolveFormKey($key);

        return $this->userForm->firstOrNew(['key' => $key, 'user_id' => auth()->id()]);
    }

    public function updateForm($key, array $attributes)
    {
        return tap($this->getForm($key)->fill(['form' => $attributes]))->saveOrFail();
    }

    protected static function resolveFormKey($key)
    {
        if (is_array($key)) {
            return implode('.', $key);
        }

        return $key;
    }
}
