<?php

namespace App\Services\Pipeliner\Contracts;

use App\Models\Pipeline\Pipeline;

interface PipelinerDataSynchronizer
{
    public function setPipeline(Pipeline $pipeline): static;

    public function countPending(): int;

    public function sync(): void;
}