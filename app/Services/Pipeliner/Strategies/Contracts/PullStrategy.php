<?php

namespace App\Services\Pipeliner\Strategies\Contracts;

use Illuminate\Database\Eloquent\Model;
use JetBrains\PhpStorm\ArrayShape;

interface PullStrategy extends SyncStrategy
{
    public function sync(object $entity): Model;

    public function syncByReference(string $reference): Model;

    public function getByReference(string $reference): object;

    #[ArrayShape(['id' => 'string', 'revision' => 'int', 'created' => \DateTimeInterface::class, 'modified' => \DateTimeInterface::class])]
    public function getMetadata(string $reference): array;
}