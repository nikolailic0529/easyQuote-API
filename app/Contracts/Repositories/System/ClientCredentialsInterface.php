<?php

namespace App\Contracts\Repositories\System;

interface ClientCredentialsInterface
{
    /**
     * Find defined Client Credentials by the specified id.
     * Return the specified attribute of the found credentials if passed.
     *
     * @param string $id
     * @param string|string|null $attribute
     * @return array|null
     */
    public function find(string $id, ?string $attribute = null);

    /**
     * Retrieve all defined Client Credentials.
     *
     * @return array
     */
    public function all(): array;
}
