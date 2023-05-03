<?php

namespace App\Foundation\Console\Contracts;

use Symfony\Component\Console\Output\OutputInterface;

interface WithOutput
{
    public function setOutput(OutputInterface $output): static;
}
