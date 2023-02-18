<?php

namespace App\Domain\User\Contracts;

interface UserForm
{
    /**
     * Update user form by specific key.
     *
     * @param string|array $key
     *
     * @return mixed
     */
    public function updateForm($key, array $attributes);

    /**
     * Retrieve user form by specific key.
     *
     * @param string|array $key
     *
     * @return mixed
     */
    public function getForm($key);
}
