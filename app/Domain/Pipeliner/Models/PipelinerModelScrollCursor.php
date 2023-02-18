<?php

namespace App\Domain\Pipeliner\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Database\Factories\PipelinerModelScrollCursorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $model_type
 * @property string|null $cursor     Base64 encoded cursor
 */
class PipelinerModelScrollCursor extends Model
{
    use Uuid;
    use HasFactory;

    protected $guarded = [];

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SalesUnit\Models\SalesUnit::class);
    }

    protected static function newFactory(): PipelinerModelScrollCursorFactory
    {
        return PipelinerModelScrollCursorFactory::new();
    }
}
