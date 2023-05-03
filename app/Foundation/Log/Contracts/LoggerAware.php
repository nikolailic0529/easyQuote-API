<?php

namespace App\Foundation\Log\Contracts;

use Psr\Log\LoggerInterface;

interface LoggerAware
{
    public function setLogger(LoggerInterface $logger): static;
}
