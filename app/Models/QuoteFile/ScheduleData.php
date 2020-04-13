<?php

namespace App\Models\QuoteFile;

use App\Traits\{
    BelongsToUser,
    BelongsToQuoteFile,
    Uuid
};
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};

class ScheduleData extends Model
{
    use Uuid, BelongsToUser, BelongsToQuoteFile, SoftDeletes;

    protected $fillable = [
        'value', 'user_id', 'quote_file_id'
    ];

    protected $hidden = [
        'user', 'quoteFile', 'created_at', 'updated_at', 'deleted_at', 'user_id', 'quote_file_id'
    ];

    protected $casts = [
        'value' => 'array'
    ];

    public function rowsHeaderToArray(): array
    {
        return [
            'from' => __('From'),
            'to' => __('To'),
            'price' => __('Price')
        ];
    }

    public function getValueAttribute($value)
    {
        if (!isset($this->attributes['value'])) {
            return null;
        }

        $keys = $this->rowsHeaderToArray();

        return collect(json_decode($this->attributes['value'], true))
            ->sortKeysByKeys($keys);
    }
}
