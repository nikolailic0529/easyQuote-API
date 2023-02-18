<?php

namespace App\Foundation\Filesystem;

class BinaryFileContent
{
    public function __construct(
        public readonly string $content,
        public readonly string $filename,
    ) {
    }
}
