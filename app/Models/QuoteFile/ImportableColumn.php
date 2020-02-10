<?php

namespace App\Models\QuoteFile;

use App\Models\{
    BaseModel,
    QuoteFile\ImportableColumnAlias
};
use App\Contracts\HasOrderedScope;
use App\Models\Quote\FieldColumn;
use App\Traits\{
    HasColumnsData,
    BelongsToUser,
    Systemable
};

class ImportableColumn extends BaseModel implements HasOrderedScope
{
    use BelongsToUser, HasColumnsData, Systemable;

    public $timestamps = false;

    protected $fillable = [
        'header', 'name', 'order'
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

    public function isDateFrom()
    {
        return $this->name === 'date_from';
    }

    public function isDateTo()
    {
        return $this->name === 'date_to';
    }
}
