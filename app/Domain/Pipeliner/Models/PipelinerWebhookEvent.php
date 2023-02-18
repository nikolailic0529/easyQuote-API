<?php

namespace App\Domain\Pipeliner\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $event
 * @property string|null $event_time
 * @property array|null  $payload
 */
class PipelinerWebhookEvent extends Model
{
    use Uuid;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'event_time' => 'datetime',
        'payload' => 'array',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(PipelinerWebhook::class);
    }
}
