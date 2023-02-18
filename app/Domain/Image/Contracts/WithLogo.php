<?php

namespace App\Domain\Image\Contracts;

interface WithLogo
{
    /**
     * With and Height for thumbnail.
     */
    public function thumbnailProperties(): array;
}
