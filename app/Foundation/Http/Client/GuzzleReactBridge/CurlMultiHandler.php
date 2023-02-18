<?php

namespace App\Foundation\Http\Client\GuzzleReactBridge;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class CurlMultiHandler extends \GuzzleHttp\Handler\CurlMultiHandler
{
    private LoopInterface $loop;
    private ?TimerInterface $timer = null;
    private int $activeRequests = 0;

    public function __construct(array $options = [])
    {
        $this->loop = Loop::get();

        $options['select_timeout'] = 0;

        parent::__construct($options);
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        ++$this->activeRequests;

        $this->updateTimer();

        $promise = parent::__invoke($request, $options);

        $completeHandler = $this->responseCompleteHandler();

        $promise->then(
            onFulfilled: $completeHandler,
            onRejected: $completeHandler,
        );

        return $promise;
    }

    private function responseCompleteHandler(): callable
    {
        return function (): void {
            --$this->activeRequests;
            $this->updateTimer();
        };
    }

    private function updateTimer(): void
    {
        // Activate timer if required and there is no active one.
        if ($this->activeRequests > 0 && null === $this->timer) {
            $this->timer = $this->loop->addPeriodicTimer(0.001, [$this, 'tick']);
        }

        // Cancel timer if there are no more active requests.
        if ($this->activeRequests <= 0 && $this->timer !== null) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
    }
}
