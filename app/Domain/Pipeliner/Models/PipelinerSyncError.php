<?php

namespace App\Domain\Pipeliner\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Database\Factories\PipelinerSyncErrorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Domain\Pipeliner\Models\PipelinerSyncError.
 *
 * @property string                          $id
 * @property string                          $entity_type
 * @property string                          $entity_id
 * @property string                          $strategy_name
 * @property string                          $error_message Error message text
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property Model                           $entity
 */
class PipelinerSyncError extends Model
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'archived_at' => 'datetime',
        'resolved_at' => 'datetime',
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
