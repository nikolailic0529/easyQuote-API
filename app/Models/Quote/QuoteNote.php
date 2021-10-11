<?php

namespace App\Models\Quote;

use App\Models\Quote\QuoteVersion;
use App\Traits\{
    Activity\LogsActivity,
    Auth\Multitenantable,
    BelongsToQuote,
    BelongsToUser,
    Uuid,
};
use App\Models\User;
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, SoftDeletes};

/**
 * @property string|null $quote_id
 * @property string|null $quote_version_id
 * @property string|null $text
 * @property bool|null $is_from_quote
 *
 * @property-read Quote $quote
 * @property-read User|null $user
 */
class QuoteNote extends Model
{
    use Uuid, Multitenantable, BelongsToUser, LogsActivity, SoftDeletes;

    protected $fillable = [
        'quote_id', 'user_id', 'text'
    ];

    protected static $logAttributes = [
        'text'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withDefault();
    }

    public function quoteVersion(): BelongsTo
    {
        return $this->belongsTo(QuoteVersion::class);
    }
}
