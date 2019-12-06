<?php

namespace App\Models\QuoteFile;

use App\Models\UuidModel;
use App\Traits\{
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

    public function rowsHeaderToArray(): array
    {
        return [
            'from' => __('From'),
            'to' => __('To'),
            'price' => __('Price')
        ];
    }

    public function getValueAttribute()
    {
        if (!isset($this->attributes['value'])) {
            return null;
        }

        $keys = $this->rowsHeaderToArray();

        return collect(json_decode($this->attributes['value'], true))
            ->sortKeysByKeys($keys);
    }

    public function getTotalPriceAttribute(): float
    {
        if (!isset($this->attributes['value'])) {
            return 0;
        }

        return collect(json_decode($this->original['value']))->sum('price');
    }
}
