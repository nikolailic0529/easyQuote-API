<?php

namespace App\Process\ProcessPool;

use App\Process\ProcessPool\Events\ProcessEvent;
use App\Process\ProcessPool\Events\ProcessFinished;
use App\Process\ProcessPool\Events\ProcessStarted;
use Iterator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Process pool allow you to run a constant number
 * of parallel processes
 */
class ProcessPool
{
    /**
     * @var Iterator
     */
    private $queue;

    /**
     * Running processes
     *
     * @var array
     */
    private array $running = [];

    /**
     * @var array
     */
    private array $options;

    /**
     * @var int
     */
    private int $concurrency;

    /**
     * @var EventDispatcher
     */
    private EventDispatcher $eventDispatcher;

    /**
     * Accept any type of iterator, inclusive Generator
     *
     * @param Iterator|Process[] $queue
     * @param array $options
     */
    public function __construct(Iterator $queue, array $options = [])
    {
        $this->eventDispatcher = new EventDispatcher;
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
     * Start and wait until all processes finishes
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
     * Start next processes until fill the concurrency limit
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
     * Get processes concurrency, default 5
     *
     * @return int
     */
    public function getConcurrency(): int
    {
        return $this->concurrency;
    }

    /**
     * @param int $concurrency
     *
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

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * @param EventDispatcher $eventDispatcher
     *
     * @return static
     */
    public function setEventDispatcher(EventDispatcher $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }
}
