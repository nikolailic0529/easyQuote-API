<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\{Casts\AsArrayObject, Model, SoftDeletes, Relations\MorphTo};
use Rennokki\QueryCache\Traits\QueryCacheable;

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
        'thumbnails' => 'array',
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
