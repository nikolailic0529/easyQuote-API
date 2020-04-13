<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
    Relations\MorphTo,
};

class Image extends Model
{
    use Uuid, SoftDeletes;

    protected $fillable = [
        'original', 'thumbnails'
    ];

    protected $casts = [
        'thumbnails' => 'array'
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getThumbnailsAttribute($value)
    {
        return optional(
            $value,
            fn ($thumbnails) => array_map(fn ($value) => asset("storage/{$value}"), json_decode($thumbnails, true))
        );
    }

    public function getOriginalImageAttribute()
    {
        return $this->attributes['original'];
    }
}
