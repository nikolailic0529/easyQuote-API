<?php

namespace App\Contracts;

interface HasImagesDirectory
{
    /**
     * Model images directory relative path
     *
     * @return string
     */
    public function imagesDirectory(): string;
}
