<?php

namespace App\Domain\CustomField\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Database\Factories\CustomFieldFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null                       $pl_reference
 * @property string|null                       $field_name
 * @property string|null                       $calc_formula
 * @property CustomField|null                  $parentField
 * @property Collection<int, CustomFieldValue> $values
 */
class CustomField extends Model
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

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
