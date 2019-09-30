<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportableColumnAlias
};
use App\Contracts\HasOrderedScope;
use App\Models\Quote\FieldColumn;
use App\Traits \ {
    HasColumnsData,
    HasSystemScope,
    BelongsToUser
};

class ImportableColumn extends UuidModel implements HasOrderedScope
{
    use BelongsToUser, HasColumnsData, HasSystemScope;

    public $timestamps = false;

    protected $fillable = [
        'header', 'name'
    ];

    protected $hidden = [
        'pivot', 'regexp'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
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
