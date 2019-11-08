<?php namespace App\Models\QuoteTemplate;

use App\Contracts\ActivatableInterface;
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
    HasQuotes
};
use App\Traits\Image\HasImages;
use Illuminate\Database\Eloquent\SoftDeletes;
use Arr;

class QuoteTemplate extends UuidModel implements ActivatableInterface
{
    use BelongsToUser,
        BelongsToTemplateFields,
        BelongsToCompany,
        BelongsToVendor,
        BelongsToCountries,
        HasQuotes,
        HasImages,
        Draftable,
        Activatable,
        Systemable,
        Searchable,
        SoftDeletes;

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

    public function isAttached()
    {
        return $this->quotes()->exists();
    }

    public function toSearchArray()
    {
        $this->load('countries:name', 'vendor');

        $this->makeHidden(['form_data', 'form_values_data']);

        return $this->toArray();
    }
}
