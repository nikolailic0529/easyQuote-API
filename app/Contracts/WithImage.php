<?php

namespace App\Contracts;

interface WithImage
{
    /**
     * Model images directory relative path
     *
     * @return string
     */
    public function imagesDirectory(): string;

    /**
     * With and Height for thumbnail
     *
     * @return array
     */
    public function thumbnailProperties(): array;
}
