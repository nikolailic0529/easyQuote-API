<?php

namespace App\Contracts;

interface AccessLoggable
{
    /**
     * @return void
     */
    public function storeAccessAttempt(Array $payload);
}