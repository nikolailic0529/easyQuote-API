<?php

namespace App\Contracts;

use Psr\Log\LoggerInterface;

interface WithLogger
{
    public function setLogger(LoggerInterface $logger): static;
}