<?php

namespace App\Models;

use App\Models\Data\Country;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $name State name
 * @property string $state_code State code
 * @property-read Country $country
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
