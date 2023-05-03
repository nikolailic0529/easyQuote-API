<?php

namespace App\Foundation\Process\Pool\Events;

class ProcessFinished extends ProcessEvent
{
    const NAME = 'process_finished';

    private ?\Throwable $exception = null;

    /**
     * @return static
     */
    public function setException(\Throwable $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }
}
