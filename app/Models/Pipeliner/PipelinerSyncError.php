<?php

namespace App\Models\Pipeliner;

use App\Traits\Uuid;
use Database\Factories\PipelinerSyncErrorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Pipeliner\PipelinerSyncError
 *
 * @property string $id
 * @property string $entity_type
 * @property string $entity_id
 * @property string $error_message Error message text
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class PipelinerSyncError extends Model
{
    use Uuid, SoftDeletes, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    protected static function newFactory(): PipelinerSyncErrorFactory
    {
        return PipelinerSyncErrorFactory::new();
    }

    public function entity(): MorphTo
    {
        return $this->morphTo('entity');
    }
}
