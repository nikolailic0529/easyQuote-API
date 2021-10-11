<?php

namespace App\Models;

use App\Casts\Thumbnails;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
    Relations\MorphTo,
};
use Rennokki\QueryCache\Traits\QueryCacheable;
use Illuminate\Support\Str;

/**
 * Class Image
 * @property array|null $thumbnails
 * @property string|null $original
 */
class Image extends Model
{
    use Uuid, SoftDeletes, QueryCacheable;

    protected $fillable = [
        'original', 'thumbnails'
    ];

    protected $casts = [
        'thumbnails' => Thumbnails::class
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getOriginalImageAttribute()
    {
        return $this->getRawOriginal('original');
    }
}
