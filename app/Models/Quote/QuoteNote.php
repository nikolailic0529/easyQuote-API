<?php

namespace App\Models\Quote;

use App\Traits\{
    Activity\LogsActivity,
    Auth\Multitenantable,
    BelongsToQuote,
    BelongsToUser,
    Uuid,
};
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};

class QuoteNote extends Model
{
    use Uuid, Multitenantable, BelongsToQuote, BelongsToUser, LogsActivity, SoftDeletes;

    protected $fillable = [
        'quote_id', 'user_id', 'text'
    ];

    protected static $logAttributes = [
        'text'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;
}
