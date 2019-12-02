<?php

namespace App\Models;

class Image extends UuidModel
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

    public function getThumbnailsAttribute()
    {
        $thumbnails = $this->attributes['thumbnails'];

        if (!isset($thumbnails)) {
            return null;
        }

        $thumbnails = collect(json_decode($thumbnails, true))->map(function ($value) {
            return asset("storage/{$value}");
        });

        return $thumbnails->toArray();
    }

    public function getOriginalImageAttribute()
    {
        return $this->attributes['original'];
    }
}
