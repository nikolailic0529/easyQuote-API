<?php

namespace App\Foundation\Process\Fork\Exceptions;

use App\Foundation\Process\Fork\Task;

class CouldNotManageTaskException extends \Exception
{
    public static function make(Task $task): self
    {
        return new self("Could not reliably manage task that uses process id {$task->pid()}");
    }
}
