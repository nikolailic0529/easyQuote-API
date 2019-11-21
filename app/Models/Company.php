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
    BelongsToUser,
    Image\HasImage,
    Image\HasLogo,
    Search\Searchable,
    Systemable,
    Quote\HasQuotes,
    QuoteTemplate\HasQuoteTemplates
};

class Company extends UuidModel implements WithImage, WithLogo, ActivatableInterface, HasOrderedScope
{
    use HasLogo,
        HasImage,
        BelongsToUser,
        Activatable,
        Searchable,
        Systemable,
        HasQuoteTemplates,
        HasQuotes;

    protected $fillable = [
        'name', 'category', 'vat', 'type', 'email', 'website', 'phone', 'default_vendor_id'
    ];

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

    public function syncVendors($vendors)
    {
        if (!is_array($vendors)) {
            return false;
        }

        return $this->vendors()->sync($vendors);
    }

    public function scopeVendor($query, string $id)
    {
        return $query->whereHas('vendors', function ($query) use ($id) {
            $query->where('vendors.id', $id);
        });
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class)->join('companies', 'companies.id', '=', 'company_vendor.company_id')
            ->orderByRaw("field(`vendors`.`id`, `companies`.`default_vendor_id`, null) desc");
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`vat`, 'GB758501125', null) desc");
    }

    public function inUse()
    {
        return $this->quotes()->exists() || $this->quoteTemplates()->exists();
    }
}
