<?php

namespace App\Models;

use App\Models\UuidModel;
use App\Contracts\{
    WithImage,
    ActivatableInterface,
    HasOrderedScope,
    WithLogo
};
use App\Traits\{
    Activatable,
    BelongsToAddresses,
    BelongsToUser,
    BelongsToVendors,
    Image\HasImage,
    Image\HasLogo,
    Search\Searchable,
    Systemable,
    Quote\HasQuotes,
    QuoteTemplate\HasQuoteTemplates
};
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends UuidModel implements WithImage, WithLogo, ActivatableInterface, HasOrderedScope
{
    use HasLogo,
        HasImage,
        BelongsToUser,
        BelongsToVendors,
        BelongsToAddresses,
        Activatable,
        Searchable,
        Systemable,
        HasQuoteTemplates,
        HasQuotes,
        LogsActivity,
        SoftDeletes;

    protected $fillable = [
        'name', 'category', 'vat', 'type', 'email', 'website', 'phone', 'default_vendor_id'
    ];

    protected static $logAttributes = [
        'name', 'category', 'vat', 'type', 'email', 'category', 'website', 'phone', 'defaultVendor.name'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected $hidden = [
        'pivot',
        'created_at',
        'updated_at',
        'drafted_at',
        'deleted_at',
        'is_system',
        'logo',
        'image'
    ];

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class)->join('companies', 'companies.id', '=', 'company_vendor.company_id')
            ->orderByRaw("field(`vendors`.`id`, `companies`.`default_vendor_id`, null) desc");
    }

    public function defaultVendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function scopeVendor($query, string $id)
    {
        return $query->whereHas('vendors', function ($query) use ($id) {
            $query->where('vendors.id', $id);
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`vat`, ?, null) desc", ['GB758501125']);
    }

    public function inUse()
    {
        return $this->quotes()->exists() || $this->quoteTemplates()->exists();
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }
}
