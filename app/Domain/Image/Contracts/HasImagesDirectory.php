<?php

namespace App\Domain\Image\Contracts;

interface HasImagesDirectory
{
    /**
     * Model images directory relative path.
     */
    public function imagesDirectory(): string;
}
