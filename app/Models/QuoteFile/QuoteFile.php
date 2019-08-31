<?php

namespace App\Models\QuoteFile;

use App\Models\UuidModel;
use App\Traits \ {
    HasUser,
    HasFileFormat,
    Draftable
};
use App\Contracts\HasOrderedScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteFile extends UuidModel implements HasOrderedScope
{
    use HasUser, HasFileFormat, Draftable, SoftDeletes;

    protected $fillable = [
        'original_file_path',
        'file_type'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
