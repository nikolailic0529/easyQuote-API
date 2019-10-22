<?php namespace App\Models\QuoteTemplate;

use App\Models\UuidModel;
use App\Traits \ {
    Activatable,
    BelongsToCompany,
    BelongsToCountries,
    BelongsToUser,
    BelongsToTemplateFields,
    BelongsToVendor,
    Draftable,
    Systemable,
    Search\Searchable,
    Collaboration\BelongsToCollaboration
};
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteTemplate extends UuidModel
{
    use BelongsToUser,
        BelongsToTemplateFields,
        BelongsToCompany,
        BelongsToVendor,
        BelongsToCountries,
        Draftable,
        Activatable,
        Systemable,
        Searchable,
        SoftDeletes,
        BelongsToCollaboration;

    protected $fillable = [
        'name', 'company_id', 'vendor_id', 'form_data', 'form_values_data'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'user_id', 'pivot'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'form_data' => 'array',
        'form_values_data' => 'array'
    ];
}
