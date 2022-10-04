<?php

namespace App\Models\System;

use App\Traits\Uuid;
use Database\Factories\CustomFieldFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $pl_reference
 * @property string|null $field_name
 * @property string|null $calc_formula
 * @property-read CustomField|null $parentField
 * @property-read Collection<int, CustomFieldValue> $values
 */
class CustomField extends Model
{
    use Uuid, SoftDeletes, HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected static function newFactory(): CustomFieldFactory
    {
        return CustomFieldFactory::new();
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class)
            ->orderByDesc('is_default')
            ->orderBy('entity_order');
    }

    public function parentField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}
