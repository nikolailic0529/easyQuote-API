<?php

namespace App\Models\QuoteFile;

use App\Models\Data\Country;
use App\Models\Quote\FieldColumn;
use App\Models\User;
use App\Traits\{Auth\Multitenantable, Uuid};
use Illuminate\Database\Eloquent\{Collection, Model, Relations\BelongsTo, Relations\HasMany, SoftDeletes};
use DateTimeInterface;

/**
 * Class ImportableColumn
 *
 * @property string|null $de_header_reference
 * @property string|null $country_id
 * @property string|null $header
 * @property string|null $name
 * @property string|null $type
 * @property int|null $order
 * @property bool|null $is_temp
 * @property bool|null $is_system
 * @property DateTimeInterface|null $created_at
 * @property DateTimeInterface|null $updated_at
 * @property string|null $activated_at
 *
 * @property-read Collection<ImportableColumnAlias>|ImportableColumnAlias[] $aliases
 * @property-read Country|null $country
 */
class ImportableColumn extends Model
{
    use Uuid,
        Multitenantable,
        SoftDeletes;

    const TYPES = ['text', 'number', 'decimal', 'date'];

    protected $guarded = [];

    public function aliases(): HasMany
    {
        return $this->hasMany(ImportableColumnAlias::class);
    }

    public function fieldColumn(): BelongsTo
    {
        return $this->belongsTo(FieldColumn::class, 'quote_field_column');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->withDefault();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
