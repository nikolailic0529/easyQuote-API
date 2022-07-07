<?php

namespace App\Services\Pipeliner\Strategies\Contracts;

use App\Models\Pipeline\Pipeline;
use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;

interface SyncStrategy
{
    public function setPipeline(Pipeline $pipeline): static;

    public function getPipeline(): ?Pipeline;

    public function countPending(): int;

    public function iteratePending(): \Traversable;

    public function getModelType(): string;

    public function isApplicableTo(object $entity): bool;
}