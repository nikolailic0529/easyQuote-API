<?php

namespace App\Models\QuoteFile;

use App\Models\{
    BaseModel,
    QuoteFile\ImportableColumnAlias
};
use App\Contracts\HasOrderedScope;
use App\Models\Quote\FieldColumn;
use App\Traits\{
    Activatable,
    HasColumnsData,
    BelongsToUser,
    Systemable,
    Activity\LogsActivity,
    Search\Searchable,
    Auth\Multitenantable,
    BelongsToCountry
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class ImportableColumn extends BaseModel implements HasOrderedScope
{
    use BelongsToUser,
        BelongsToCountry,
        Multitenantable,
        HasColumnsData,
        Systemable,
        LogsActivity,
        SoftDeletes,
        Searchable,
        Activatable;

    const TYPES = ['text', 'number', 'decimal', 'date'];

    protected $fillable = [
        'header', 'name', 'order', 'is_temp', 'type', 'country_id'
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

    public function toSearchArray()
    {
        return [
            'header'        => $this->header,
            'type'          => $this->type,
            'country_name'  => $this->country->name,
            'aliases'       => $this->aliases->pluck('alias')->toArray()
        ];
    }
}
