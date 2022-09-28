<?php

namespace App\Foundation\File;

class BinaryFileContent
{
    public function __construct(
        public readonly string $content,
        public readonly string $filename,
    )
    {
    }
}