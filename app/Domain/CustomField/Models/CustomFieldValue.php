<?php

namespace App\Domain\CustomField\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Database\Factories\CustomFieldValueFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomFieldValue.
 *
 * @property string|null                                $pl_reference
 * @property string|null                                $field_value
 * @property float|null                                 $calc_value
 * @property int|null                                   $entity_order
 * @property bool|null                                  $is_default
 * @property Collection<int, CustomFieldValueAllowedBy> $allowedByRelations
 * @property Collection<int, CustomFieldValueAllowedBy> $allowedForRelations
 * @property Collection<int, CustomFieldValue>          $allowedBy
 * @property Collection<int, CustomFieldValue>          $allowedFor
 */
class CustomFieldValue extends Model
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): CustomFieldValueFactory
    {
        return CustomFieldValueFactory::new();
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }

    public function allowedByRelations(): HasMany
    {
        return $this->hasMany(CustomFieldValueAllowedBy::class, 'field_value_id');
    }

    public function allowedForRelations(): HasMany
    {
        return $this->hasMany(CustomFieldValueAllowedBy::class, 'allowed_by_id');
    }

    public function allowedBy(): BelongsToMany
    {
        return $this->belongsToMany(
            related: CustomFieldValue::class,
            table: (new CustomFieldValueAllowedBy())->getTable(),
            foreignPivotKey: 'field_value_id',
            relatedPivotKey: 'allowed_by_id'
        )
            ->using(CustomFieldValueAllowedBy::class);
    }

    public function allowedFor(): BelongsToMany
    {
        return $this->belongsToMany(
            related: CustomFieldValue::class,
            table: (new CustomFieldValueAllowedBy())->getTable(),
            foreignPivotKey: 'allowed_by_id',
            relatedPivotKey: 'field_value_id',
        )
            ->using(CustomFieldValueAllowedBy::class);
    }
}
