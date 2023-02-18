<?php

namespace App\Foundation\Error;

interface ErrorReporter
{
    public function __invoke(\Throwable $e): void;
}
