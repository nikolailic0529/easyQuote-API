<?php

namespace App\Domain\Address\Models;

use App\Domain\Country\Models\Country;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Contracts\HasOwner;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null                             $pl_reference
 * @property string|null                             $country_id
 * @property mixed|string                            $address_type
 * @property mixed|null                              $address_1
 * @property mixed|null                              $address_2
 * @property mixed|null                              $city
 * @property mixed|null                              $post_code
 * @property mixed|null                              $state
 * @property mixed|null                              $state_code
 * @property \App\Domain\Country\Models\Country|null $country
 */
class ImportedAddress extends Model implements HasOwner
{
    use Uuid;

    protected $guarded = [];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
