<?php

namespace App\Models\Quote;

use App\Contracts\SearchableEntity;
use App\Models\Company;
use App\Models\ContractType;
use App\Models\Data\Currency;
use App\Models\Opportunity;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\SalesOrder;
use App\Models\Task;
use App\Models\Template\QuoteTemplate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $id
 * @property string|null $active_version_id
 * @property string|null $contract_type_id
 * @property string|null $user_id
 * @property string|null $worldwide_quote_id
 * @property string|null $company_id
 * @property string|null $opportunity_id
 * @property string|null $quote_currency_id
 * @property string|null $output_currency_id
 * @property string|null $quote_template_id
 * @property float|null $exchange_rate_margin
 * @property int|null $completeness
 * @property string|null $quote_expiry_date
 * @property string|null $closing_date
 * @property array|null $checkbox_status
 * @property string|null $assets_migrated_at
 * @property string|null $submitted_at
 * @property string|null $activated_at
 * @property User $user
 * @property float|null $buy_price
 * @property float|null actual_exchange_rate_value
 * @property string|null $additional_notes
 * @property string|null $quote_number
 * @property int|null $sequence_number
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
 *
 * @property float|null $total_price
 * @property float|null $margin_percentage
 * @property float|null $final_total_price
 * @property float|null $final_total_price_excluding_tax
 * @property float|null $applicable_discounts_value
 * @property float|null $final_margin
 *
 * @property-read bool|null $sales_order_exists
 *
 * @property SalesOrder|null $salesOrder
 * @property ContractType|null $contractType
 * @property Opportunity|null $opportunity
 * @property Collection|WorldwideDistribution[] $worldwideDistributions
 * @property QuoteTemplate|null $quoteTemplate
 * @property Currency $quoteCurrency
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
 */
class WorldwideQuote extends BaseWorldwideQuote
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuoteVersion::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WorldwideQuoteVersion::class);
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }
}
