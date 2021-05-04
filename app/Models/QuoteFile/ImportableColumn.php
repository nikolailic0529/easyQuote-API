<?php

namespace App\Models\QuoteFile;

use App\Contracts\{HasOrderedScope, ReindexQuery, SearchableEntity};
use App\Models\Quote\FieldColumn;
use App\Traits\{Activatable,
    Activity\LogsActivity,
    Auth\Multitenantable,
    BelongsToCountry,
    BelongsToUser,
    Search\Searchable,
    Systemable,
    Uuid
};
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo, Relations\HasMany, SoftDeletes,};
use Illuminate\Support\Str;

/**
 * Class ImportableColumn
 *
 * @property string|null $header
 * @property string|null $name
 * @property bool|null $is_temp
 * @property bool|null $is_system
 */
class ImportableColumn extends Model implements HasOrderedScope, ReindexQuery, SearchableEntity
{
    use Uuid,
        BelongsToUser,
        BelongsToCountry,
        Multitenantable,
        Systemable,
        LogsActivity,
        SoftDeletes,
        Searchable,
        Activatable,
        EloquentJoin;

    const TYPES = ['text', 'number', 'decimal', 'date'];

    protected $fillable = [
        'header', 'name', 'order', 'is_temp', 'type', 'country_id', 'is_system'
    ];

    protected static $logAttributes = [
        'header', 'type', 'country.name', 'aliases:parsed_aliases'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('is_temp', false);
    }

    public static function reindexQuery(): Builder
    {
        return static::regular();
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ImportableColumnAlias::class);
    }

    public function fieldColumn(): BelongsTo
    {
        return $this->belongsTo(FieldColumn::class, 'quote_field_column');
    }

    public function setHeaderAttribute($value): void
    {
        $this->attributes['header'] = $value;

        if (!isset($this->attributes['name'])) {
            $this->attributes['name'] = Str::slug($value, '_');
        }
    }

    public function getParsedAliasesAttribute(): string
    {
        return $this->aliases->pluck('alias')->implode(', ');
    }

    public function toSearchArray(): array
    {
        return [
            'header' => $this->header,
            'type' => $this->type,
            'country_name' => $this->country->name,
            'aliases' => $this->aliases->pluck('alias')->toArray()
        ];
    }

    public function getItemNameAttribute()
    {
        return "Importable Column ({$this->header})";
    }
}
