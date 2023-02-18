<?php

namespace App\Domain\Pipeliner\Integration\Models;

use JetBrains\PhpStorm\Pure;

class StepEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $percent,
        public readonly int $sortOrder,
        public readonly ?PipelineEntity $pipeline = null
    ) {
    }

    #[Pure]
    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            name: $array['name'],
            percent: $array['percent'],
            sortOrder: $array['sortOrder'],
            pipeline: PipelineEntity::tryFromArray($array['pipeline'] ?? null)
        );
    }

    public function getQualifiedStepName(): string
    {
        return sprintf('%d. %s', $this->sortOrder + 1, $this->name);
    }
}
