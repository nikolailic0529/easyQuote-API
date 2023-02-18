<?php

namespace App\Domain\QuoteFile\Models;

use App\Domain\QuoteFile\Concerns\{BelongsToQuoteFile};
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array $value
 */
class ScheduleData extends Model
{
    use Uuid;
    use BelongsToQuoteFile;
    use SoftDeletes;

    protected $fillable = [
        'value', 'quote_file_id',
    ];

    protected $hidden = [
        'user', 'quoteFile', 'created_at', 'updated_at', 'deleted_at', 'quote_file_id',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function rowsHeaderToArray(): array
    {
        return [
            'from' => __('From'),
            'to' => __('To'),
            'price' => __('Price'),
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
