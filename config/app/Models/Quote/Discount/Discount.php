<?php namespace App\Models\Quote\Discount;

use App\Models\UuidModel;
use App\Traits \ {
    Activatable,
    BelongsToCountry,
    BelongsToUser,
    BelongsToVendor,
    Search\Searchable
};

abstract class Discount extends UuidModel
{
    use Activatable, Searchable, BelongsToCountry, BelongsToVendor, BelongsToUser;

    public function getFillable()
    {
        $fillable = [
            'country_id', 'vendor_id', 'name'
        ];

        return array_merge($this->fillable, array_diff($fillable, $this->guarded));
    }

    public function toSearchArray()
    {
        $this->load('country', 'vendor');

        return $this->toArray();
    }
}
