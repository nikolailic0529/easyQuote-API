<?php

namespace App\Models;

class Image extends BaseModel
{
    protected $fillable = [
        'original', 'thumbnails'
    ];

    protected $casts = [
        'thumbnails' => 'array'
    ];

    public function imageable()
    {
        return $this->morphTo();
    }

    public function getThumbnailsAttribute($thumbnails)
    {
        if (is_null($thumbnails)) {
            return $thumbnails;
        }

        $thumbnails = array_map(fn ($value) => asset("storage/{$value}"), json_decode($thumbnails, true));

        return $thumbnails;
    }

    public function getOriginalImageAttribute()
    {
        return $this->attributes['original'];
    }
}
