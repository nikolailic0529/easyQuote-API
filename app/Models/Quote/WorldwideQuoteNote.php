<?php

namespace App\Models\Quote;

use App\Models\User;
use App\Traits\Activity\LogsActivity;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WorldwideQuoteNote
 *
 * @property string|null $text
 * @property string|null $user_id
 * @property string|null $worldwide_quote_id
 * @property WorldwideQuote|null $worldwideQuote
 */
class WorldwideQuoteNote extends Model
{
    use Uuid, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected static array $logAttributes = ['text'];

    protected static bool $logOnlyDirty = true;

    protected static bool $submitEmptyLogs = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function worldwideQuote(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuote::class);
    }
}
