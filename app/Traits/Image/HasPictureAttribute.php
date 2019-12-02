<?php

namespace App\Traits\Image;

trait HasPictureAttribute
{
    public function getPictureAttribute()
    {
        if (!isset($this->image->original_image)) {
            return null;
        }

        return asset('storage/' . $this->image->original_image);
    }
}
