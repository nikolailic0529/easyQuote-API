<?php

namespace App\Models;

use App\Models\Quote\WorldwideDistribution;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OpportunitySupplier
 *
 * @property string|null $opportunity_id
 * @property string|null $supplier_name
 * @property string|null $country_name
 * @property string|null $contact_name
 * @property string|null $contact_email
 *
 * @property-read Opportunity|null $opportunity
 * @property-read WorldwideDistribution|null $worldwideDistribution
 */
class OpportunitySupplier extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function worldwideDistribution(): HasOne
    {
        return $this->hasOne(WorldwideDistribution::class);
    }
}
