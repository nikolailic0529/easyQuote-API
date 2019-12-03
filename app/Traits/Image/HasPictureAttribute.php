<?php

namespace App\Traits\Image;

trait HasPictureAttribute
{
    public function initializeHasPictureAttribute()
    {
        $this->hidden = array_merge($this->hidden, ['image', 'image_id']);
        $this->appends = array_merge($this->appends, ['picture']);
    }

    public function getPictureAttribute()
    {
        if (!isset($this->image->original_image)) {
            return null;
        }

        return asset('storage/' . $this->image->original_image);
    }
}
