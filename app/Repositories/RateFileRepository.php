<?php

namespace App\Repositories;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Contracts\Filesystem\Filesystem as Disk;
use Illuminate\Support\Collection;

class RateFileRepository
{
    protected FilesystemAdapter $disk;

    public function __construct(Disk $disk)
    {
        $this->disk = $disk;
    }

    public function getAll(): Collection
    {
        return collect($this->disk->allFiles())
            ->sortBy(fn (string $filename) => $this->disk->lastModified($filename))
            ->values();
    }

    public function getAllNames(): array
    {
        return $this->getAll()->map(fn (string $filename) => $filename)->toArray();
    }

    public function path(string $filename): string
    {
        return $this->disk->path($filename);
    }

    public static function make(Disk $disk)
    {
        return new static($disk);
    }
}
