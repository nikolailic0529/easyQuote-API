<?php

namespace App\Models;

use App\Models\Data\Country;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $country_id
 * @property mixed|string $address_type
 * @property mixed|null $address_1
 * @property mixed|null $address_2
 * @property mixed|null $city
 * @property mixed|null $post_code
 * @property mixed|null $state
 * @property mixed|null $state_code
 */
class ImportedAddress extends Model
{
    use Uuid;

    protected $guarded = [];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
