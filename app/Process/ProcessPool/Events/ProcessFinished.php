<?php

namespace App\Process\ProcessPool\Events;

use Throwable;

class ProcessFinished extends ProcessEvent
{
    const NAME = 'process_finished';

    private ?Throwable $exception = null;

    /**
     * @param Throwable $exception
     *
     * @return static
     */
    public function setException(Throwable $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }
}
