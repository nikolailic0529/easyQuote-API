<?php

namespace App\Contracts;

use Symfony\Component\Console\Output\OutputInterface;

interface WithOutput
{
    public function setOutput(OutputInterface $output): static;
}