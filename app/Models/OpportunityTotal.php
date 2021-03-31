<?php

namespace App\Models;

use App\Models\Data\Country;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class OpportunityTotal
 *
 * @property string|null $user_id
 * @property string|null $opportunity_id
 * @property float|null $base_opportunity_amount
 * @property int|null $opportunity_status
 * @property string|null $opportunity_created_at
 */
class OpportunityTotal extends Model
{
    use Uuid;

    protected $guarded = [];

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }
}
