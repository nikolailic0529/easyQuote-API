<?php

namespace App\Process\ProcessPool\Events;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\Process\Process;

abstract class ProcessEvent extends Event
{
    /**
     * @var Process
     */
    private Process $process;

    /**
     * @param Process $process
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * @return Process
     */
    public function getProcess(): Process
    {
        return $this->process;
    }
}
