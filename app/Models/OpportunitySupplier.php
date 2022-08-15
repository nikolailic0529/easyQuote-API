<?php

namespace App\Models;

use App\Models\Data\Country;
use App\Models\Quote\WorldwideDistribution;
use App\Traits\Uuid;
use Database\Factories\OpportunitySupplierFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property-read Collection<WorldwideDistribution>|WorldwideDistribution[] $distributorQuotes
 * @property-read Country|null $country
 */
class OpportunitySupplier extends Model
{
    use Uuid, SoftDeletes, HasFactory;

    protected $guarded = [];

    protected static function newFactory(): OpportunitySupplierFactory
    {
        return OpportunitySupplierFactory::new();
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function distributorQuotes(): HasMany
    {
        return $this->hasMany(WorldwideDistribution::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_name', 'name');
    }
}
