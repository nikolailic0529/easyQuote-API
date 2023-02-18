<?php

namespace App\Foundation\Http\Client\GuzzleReactBridge;

use GuzzleHttp\Promise\PromiseInterface;
use React\Promise\Deferred;

class Utils
{
    public static function adapt(PromiseInterface $promise): \React\Promise\PromiseInterface
    {
        $deferred = new Deferred();

        $promise->then(
            onFulfilled: static function (mixed $r) use ($deferred): void {
                $deferred->resolve($r);
            },
            onRejected: static function (mixed $r) use ($deferred): void {
                $deferred->reject($r);
            }
        );

        return $deferred->promise();
    }
}
