<?php

namespace App\Domain\Pipeliner\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $reference
 * @property string $type
 * @property array  $data
 */
class PipelinerSnapshotEntry extends Model
{
    use Uuid;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(PipelinerSnapshot::class);
    }
}
