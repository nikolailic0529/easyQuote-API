<?php

namespace App\Domain\Address\Models;

use App\Domain\Country\Models\Country;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string                             $name       State name
 * @property string                             $state_code State code
 * @property \App\Domain\Country\Models\Country $country
 */
class State extends Model
{
    use Uuid;

    protected $guarded = [];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
