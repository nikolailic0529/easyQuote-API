<?php

namespace App\Domain\QuoteFile\Models;

use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Country\Models\Country;
use App\Domain\Rescue\Models\FieldColumn;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ImportableColumn.
 *
 * @property string|null                                               $de_header_reference
 * @property string|null                                               $country_id
 * @property string|null                                               $header
 * @property string|null                                               $name
 * @property string|null                                               $type
 * @property int|null                                                  $order
 * @property bool|null                                                 $is_temp
 * @property bool|null                                                 $is_system
 * @property \DateTimeInterface|null                                   $created_at
 * @property \DateTimeInterface|null                                   $updated_at
 * @property string|null                                               $activated_at
 * @property Collection<ImportableColumnAlias>|ImportableColumnAlias[] $aliases
 * @property Country|null                                              $country
 */
class ImportableColumn extends Model
{
    use Uuid;
    use Multitenantable;
    use SoftDeletes;

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
        return $this->belongsTo(\App\Domain\User\Models\User::class);
    }
}
