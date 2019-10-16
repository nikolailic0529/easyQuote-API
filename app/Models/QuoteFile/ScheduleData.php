<?php namespace App\Models\QuoteFile;

use App\Models\UuidModel;
use App\Traits \ {
    BelongsToUser,
    BelongsToQuoteFile
};

class ScheduleData extends UuidModel
{
    use BelongsToUser, BelongsToQuoteFile;

    protected $fillable = [
        'value', 'user_id', 'quote_file_id'
    ];

    protected $hidden = [
        'user', 'quoteFile', 'created_at', 'updated_at', 'deleted_at', 'user_id', 'quote_file_id'
    ];

    protected $casts = [
        'value' => 'array'
    ];
}
