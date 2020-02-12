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
    Auth\Multitenantable
};
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportableColumn extends BaseModel implements HasOrderedScope
{
    use BelongsToUser,
        Multitenantable,
        HasColumnsData,
        Systemable,
        LogsActivity,
        SoftDeletes,
        Searchable,
        Activatable;

    public $timestamps = false;

    protected $fillable = [
        'header', 'name', 'order', 'is_temp'
    ];

    protected $hidden = [
        'pivot', 'regexp'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function aliases()
    {
        return $this->hasMany(ImportableColumnAlias::class);
    }

    public function fieldColumn()
    {
        return $this->belongsTo(FieldColumn::class, 'quote_field_column');
    }
}
