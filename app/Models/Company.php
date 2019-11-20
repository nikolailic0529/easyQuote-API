<?php

namespace App\Models;

use App\Models\UuidModel;
use App\Contracts\{
    WithImage,
    ActivatableInterface,
    WithLogo
};
use App\Traits\{
    Activatable,
    BelongsToUser,
    BelongsToVendors,
    Image\HasImage,
    Image\HasLogo,
    Search\Searchable,
    Systemable
};

class Company extends UuidModel implements WithImage, WithLogo, ActivatableInterface
{
    use HasLogo,
        HasImage,
        BelongsToVendors,
        BelongsToUser,
        Activatable,
        Searchable,
        Systemable;

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
}
