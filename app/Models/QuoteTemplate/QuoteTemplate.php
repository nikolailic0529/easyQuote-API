<?php namespace App\Models\QuoteTemplate;

use App\Models \ {
    UuidModel,
    QuoteTemplate\TemplateField,
    Data\Country,
    Company,
    Vendor
};
use App\Traits \ {
    Activatable,
    BelongsToCompany,
    BelongsToCountries,
    BelongsToUser,
    BelongsToTemplateFields,
    BelongsToVendor,
    Draftable,
    Systemable
};

class QuoteTemplate extends UuidModel
{
    use BelongsToUser, BelongsToTemplateFields, BelongsToCompany, BelongsToVendor, BelongsToCountries, Draftable, Activatable, Systemable;

    protected $fillable = [
        'name', 'company_id', 'vendor_id'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'user_id'
    ];

    protected $casts = [
        'is_system' => 'boolean'
    ];
}
