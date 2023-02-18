<?php

namespace App\Domain\Pipeliner\Services\Contracts;

use App\Domain\Pipeline\Models\Pipeline;

interface PipelinerDataSynchronizer
{
    public function setPipeline(Pipeline $pipeline): static;

    public function countPending(): int;

    public function sync(): void;
}
