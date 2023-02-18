<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Country\Models\Country;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Database\Factories\OpportunitySupplierFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OpportunitySupplier.
 *
 * @property string|null                                               $opportunity_id
 * @property string|null                                               $supplier_name
 * @property string|null                                               $country_name
 * @property string|null                                               $contact_name
 * @property string|null                                               $contact_email
 * @property int|null                                                  $entity_order
 * @property Opportunity|null                                          $opportunity
 * @property Collection<WorldwideDistribution>|WorldwideDistribution[] $distributorQuotes
 * @property \App\Domain\Country\Models\Country|null                   $country
 */
class OpportunitySupplier extends Model
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

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
