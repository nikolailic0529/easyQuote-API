<?php

namespace App\Contracts;

interface WithLogo
{
    /**
     * With and Height for thumbnail
     *
     * @return array
     */
    public function thumbnailProperties(): array;
}
