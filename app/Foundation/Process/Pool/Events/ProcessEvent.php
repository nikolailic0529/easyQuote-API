<?php

namespace App\Foundation\Process\Pool\Events;

use Symfony\Component\Process\Process;
use Symfony\Contracts\EventDispatcher\Event;

abstract class ProcessEvent extends Event
{
    private Process $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}
