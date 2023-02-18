<?php

namespace App\Domain\Image\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

/**
 * Class Image.
 *
 * @property array|null  $thumbnails
 * @property string|null $original
 */
class Image extends Model
{
    use Uuid;
    use SoftDeletes;
    use QueryCacheable;

    protected $fillable = [
        'original', 'thumbnails',
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
