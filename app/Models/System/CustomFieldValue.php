<?php

namespace App\Models\System;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomFieldValue
 *
 * @property string|null $field_value
 * @property int|null $entity_order
 * @property bool|null $is_default
 */
class CustomFieldValue extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}
