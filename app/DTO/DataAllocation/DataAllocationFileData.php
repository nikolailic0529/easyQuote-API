<?php

namespace App\DTO\DataAllocation;

use Spatie\LaravelData\Data;

class DataAllocationFileData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $filepath,
        public readonly string $filename,
        public readonly string $extension,
        public readonly int $size,
        public readonly \DateTimeInterface $created_at,
        public readonly \DateTimeInterface $updated_at,
        public readonly ?\DateTimeInterface $imported_at
    ) {
    }
}