<?php

namespace App\Models;

use App\Casts\AsMessageBag;
use App\Traits\Uuid;
use Database\Factories\OpportunityValidationResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\MessageBag;

/**
 * @property MessageBag|null $messages
 * @property bool|null $is_passed
 */
class OpportunityValidationResult extends Model
{
    use Uuid, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'messages' => AsMessageBag::class,
        'is_passed' => 'boolean',
    ];

    protected static function newFactory(): OpportunityValidationResultFactory
    {
        return OpportunityValidationResultFactory::new();
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
