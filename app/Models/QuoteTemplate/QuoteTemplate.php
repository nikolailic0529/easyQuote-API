<?php namespace App\Models\QuoteTemplate;

use App\Models\BaseModel;
use App\Contracts\ActivatableInterface;
use App\Traits \ {
    Activatable,
    BelongsToCompany,
    BelongsToCountries,
    BelongsToCurrency,
    BelongsToUser,
    BelongsToTemplateFields,
    BelongsToVendor,
    Draftable,
    Systemable,
    Search\Searchable,
    HasQuotes,
    Image\HasImages,
    Activity\LogsActivity
};
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteTemplate extends BaseModel implements ActivatableInterface
{
    use BelongsToUser,
        BelongsToTemplateFields,
        BelongsToCompany,
        BelongsToVendor,
        BelongsToCountries,
        BelongsToCurrency,
        HasQuotes,
        HasImages,
        Draftable,
        Activatable,
        Systemable,
        Searchable,
        SoftDeletes,
        LogsActivity;

    protected $fillable = [
        'name', 'company_id', 'vendor_id', 'form_data', 'form_values_data'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'drafted_at', 'deleted_at', 'user_id', 'pivot', 'form_data', 'form_values_data'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'form_data' => 'array',
        'form_values_data' => 'array'
    ];

    protected static $logAttributes = [
        'name', 'company.name', 'vendor.name', 'currency.symbol'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function isAttached()
    {
        return $this->quotes()->exists();
    }

    public function toSearchArray()
    {
        $this->load('countries:name', 'vendor', 'company');

        $this->makeHidden(['form_data', 'form_values_data']);

        return $this->toArray();
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }
}
