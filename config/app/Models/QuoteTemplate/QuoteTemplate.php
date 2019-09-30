<?php namespace App\Models\QuoteTemplate;

use App\Models \ {
    UuidModel,
    QuoteTemplate\TemplateField,
    Data\Country,
    Company,
    Vendor
};
use App\Traits \ {
    BelongsToUser,
    BelongsToTemplateFields,
    Draftable
};

class QuoteTemplate extends UuidModel
{
    use BelongsToUser, BelongsToTemplateFields, Draftable;

    protected $fillable = [
        'name'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'user_id'
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_quote_template');
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'vendor_quote_template');
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'country_quote_template');
    }
}
