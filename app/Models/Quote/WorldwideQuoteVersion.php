<?php

namespace App\Models\Quote;

use App\Models\Address;
use App\Models\Addressable;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContractType;
use App\Models\Data\Currency;
use App\Models\Opportunity;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\Template\QuoteTemplate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Models\WorldwideQuoteAssetsGroup;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * Class WorldwideQuoteVersion
 *
 * /**
 * @property string|null $id
 * @property string|null $worldwide_quote_id
 * @property string|null $user_id
 * @property string|null $company_id
 * @property string|null $quote_currency_id
 * @property string|null $buy_currency_id
 * @property string|null $output_currency_id
 * @property string|null $quote_template_id
 * @property float|null $exchange_rate_margin
 * @property int|null $completeness
 * @property string|null $quote_expiry_date
 * @property string|null $closing_date
 * @property string|null $assets_migrated_at
 * @property float|null $buy_price
 * @property float|null $actual_exchange_rate_value
 * @property string|null $additional_notes
 * @property int|null $user_version_sequence_number
 * @property string|null $quote_type
 * @property float|null $tax_value
 * @property float|null $margin_value
 * @property string|null $margin_method
 * @property float|null $custom_discount
 * @property string|null $payment_terms
 * @property string|null $pricing_document
 * @property string|null $service_agreement_id
 * @property string|null $system_handle
 * @property string|null $additional_details
 * @property string|null $sort_rows_column
 * @property string|null $sort_rows_direction
 * @property string|null $sort_assets_groups_column
 * @property string|null $sort_assets_groups_direction
 * @property bool|null $use_groups
 *
 * @property float|null $total_price
 * @property float|null $margin_percentage
 * @property float|null $final_total_price
 * @property float|null $final_total_price_excluding_tax
 * @property float|null $applicable_discounts_value
 * @property float|null $final_margin
 *
 * @property User $user
 * @property ContractType|null $contractType
 * @property Collection<WorldwideDistribution>|WorldwideDistribution[] $worldwideDistributions
 * @property QuoteTemplate|null $quoteTemplate
 * @property Currency $quoteCurrency
 * @property Currency|null $buyCurrency
 * @property Currency $outputCurrency
 * @property Company|null $company
 * @property Collection<WorldwideQuoteAsset>|WorldwideQuoteAsset[] $assets
 * @property MultiYearDiscount|null $multiYearDiscount
 * @property PrePayDiscount|null $prePayDiscount
 * @property PromotionalDiscount|null $promotionalDiscount
 * @property SND|null $snDiscount
 * @property Collection|null $applicableSnDiscounts
 * @property Collection|null $applicablePromotionalDiscounts
 * @property Collection|null $applicablePrePayDiscounts
 * @property Collection|null $applicableMultiYearDiscounts
 * @property WorldwideQuote|null $worldwideQuote
 *
 * @property-read Collection<Address>|Address[] $addresses
 * @property-read Collection<Contact>|Contact[] $contacts
 * @property-read WorldwideQuoteNote|null $note
 * @property-read WorldwideQuoteNote|null $draftNote
 * @property-read WorldwideQuoteNote|null $submitNote
 * @property-read Collection<WorldwideQuoteAssetsGroup>|WorldwideQuoteAssetsGroup[] $assetsGroups
 */
class WorldwideQuoteVersion extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function worldwideQuote(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuote::class);
    }

    public function assets(): MorphMany
    {
        return tap($this->morphMany(WorldwideQuoteAsset::class, 'worldwide_quote'), function (MorphMany $relation) {
            $relation->addSelect([
                'vendor_short_code' => Vendor::query()->select('short_code')
                    ->from('vendors')
                    ->whereColumn('vendors.id', 'worldwide_quote_assets.vendor_id')->limit(1)->toBase()
            ])
                ->oldest($relation->getRelated()->getQualifiedCreatedAtColumn());
        });
    }

    public function assetsGroups(): HasMany
    {
        return tap($this->hasMany(WorldwideQuoteAssetsGroup::class), function (HasMany $relation) {
            $relation
                ->withCount('assets')
                ->withSum('assets', 'price')
                ->withCasts(['rows_sum' => 'float'])
                ->oldest($relation->getRelated()->getQualifiedCreatedAtColumn());
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quoteCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function buyCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function outputCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function quoteTemplate(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function worldwideDistributions(): MorphMany
    {
        return $this->morphMany(WorldwideDistribution::class, 'worldwide_quote')
            // the clause is required to sync Worldwide Distribution with Opportunity Supplier Entities
            ->has('opportunitySupplier');
    }

    public function multiYearDiscount(): BelongsTo
    {
        return $this->belongsTo(MultiYearDiscount::class);
    }

    public function prePayDiscount(): BelongsTo
    {
        return $this->belongsTo(PrePayDiscount::class);
    }

    public function promotionalDiscount(): BelongsTo
    {
        return $this->belongsTo(PromotionalDiscount::class);
    }

    public function snDiscount(): BelongsTo
    {
        return $this->belongsTo(SND::class);
    }

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    public function note(): HasOne
    {
        return $this->hasOne(WorldwideQuoteNote::class)
            ->latest();
    }

    public function draftNote(): HasOne
    {
        return $this->hasOne(WorldwideQuoteNote::class)
            ->where('is_for_submitted_quote', false)
            ->latest();
    }

    public function submitNote(): HasOne
    {
        return $this->hasOne(WorldwideQuoteNote::class)
            ->where('is_for_submitted_quote', true)
            ->latest();
    }
}
