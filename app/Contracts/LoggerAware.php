<?php

namespace App\Contracts;

use Psr\Log\LoggerInterface;

interface LoggerAware
{
    public function setLogger(LoggerInterface $logger): static;
}