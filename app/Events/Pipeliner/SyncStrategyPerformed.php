<?php

namespace App\Events\Pipeliner;

final class SyncStrategyPerformed
{
    public function __construct(
        public readonly string $strategyClass,
        public readonly string $entityReference,
    )
    {
    }
}