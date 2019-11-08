<?php namespace App\Traits\Image;

use App\Models\Image;

trait HasImages
{
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
