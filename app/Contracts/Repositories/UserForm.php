<?php

namespace App\Contracts\Repositories;

interface UserForm
{
    /**
     * Update user form by specific key.
     *
     * @param string|array $key
     * @param array $attributes
     * @return mixed
     */
    public function updateForm($key, array $attributes);

    /**
     * Retrieve user form by specific key.
     *
     * @param string|array $key
     * @return mixed
     */
    public function getForm($key);
}