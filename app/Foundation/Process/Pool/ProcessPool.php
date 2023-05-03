<?php

namespace App\Foundation\Process\Pool;

use App\Foundation\Process\Pool\Events\ProcessEvent;
use App\Foundation\Process\Pool\Events\ProcessFinished;
use App\Foundation\Process\Pool\Events\ProcessStarted;
use Iterator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Process pool allow you to run a constant number
 * of parallel processes.
 */
class ProcessPool
{
    /**
     * @var \Iterator
     */
    private $queue;

    /**
     * Running processes.
     */
    private array $running = [];

    private array $options;

    private int $concurrency;

    private EventDispatcher $eventDispatcher;

    /**
     * Accept any type of iterator, inclusive Generator.
     *
     * @param \Iterator|Process[] $queue
     */
    public function __construct(\Iterator $queue, array $options = [])
    {
        $this->eventDispatcher = new EventDispatcher();
        $this->queue = $queue;
        $this->options = array_merge($this->getDefaultOptions(), $options);
        $this->concurrency = $this->options['concurrency'];
    }

    private function getDefaultOptions(): array
    {
        return [
            'concurrency' => '5',
            'eventPrefix' => 'process_pool',
            'throwExceptions' => false,
        ];
    }

    /**
     * Start and wait until all processes finishes.
     *
     * @return void
     */
    public function wait()
    {
        $this->startNextProcesses();

        while (count($this->running) > 0) {
            /** @var Process $process */
            foreach ($this->running as $key => $process) {
                $exception = null;
                try {
                    $process->checkTimeout();
                    $isRunning = $process->isRunning();
                } catch (RuntimeException $e) {
                    $isRunning = false;
                    $exception = $e;

                    if ($this->shouldThrowExceptions()) {
                        throw $e;
                    }
                }

                if (!$isRunning) {
                    unset($this->running[$key]);
                    $this->startNextProcesses();

                    $event = new ProcessFinished($process);

                    if ($exception) {
                        $event->setException($exception);
                    }

                    $this->dispatchEvent($event);
                }
            }
            usleep(1000);
        }
    }

    public function onProcessFinished(callable $callback)
    {
        $eventName = $this->options['eventPrefix'].'.'.ProcessFinished::NAME;
        $this->getEventDispatcher()->addListener($eventName, $callback);
    }

    /**
     * Start next processes until fill the concurrency limit.
     *
     * @return void
     */
    private function startNextProcesses()
    {
        $concurrency = $this->getConcurrency();

        while (count($this->running) < $concurrency && $this->queue->valid()) {
            $process = $this->queue->current();
            $process->start();

            $this->dispatchEvent(new ProcessStarted($process));

            $this->running[] = $process;

            $this->queue->next();
        }
    }

    private function shouldThrowExceptions()
    {
        return $this->options['throwExceptions'];
    }

    /**
     * Get processes concurrency, default 5.
     */
    public function getConcurrency(): int
    {
        return $this->concurrency;
    }

    /**
     * @return static
     */
    public function setConcurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    private function dispatchEvent(ProcessEvent $event)
    {
        $eventPrefix = $this->options['eventPrefix'];
        $eventName = $event::NAME;

        $this->getEventDispatcher()->dispatch($event, "$eventPrefix.$eventName");
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * @return static
     */
    public function setEventDispatcher(EventDispatcher $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }
}
