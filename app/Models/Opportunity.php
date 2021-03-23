<?php

namespace App\Models;

use App\Contracts\SearchableEntity;
use App\Models\Quote\WorldwideQuote;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Opportunity
 *
 * @property string|null $user_id
 * @property string|null $contract_type_id
 * @property string|null $primary_account_id
 * @property string|null $primary_account_contact_id
 * @property string|null $account_manager_id
 * @property string|null $project_name
 * @property string|null $nature_of_service
 * @property string|null $renewal_month
 * @property int|null $renewal_year
 * @property string|null $customer_status
 * @property string|null $end_user_name
 * @property string|null $hardware_status
 * @property string|null $region_name
 *
 * @property string|null $opportunity_start_date
 * @property string|null $opportunity_end_date
 * @property string|null $opportunity_closing_date
 * @property string|null $customer_order_date
 * @property string|null $purchase_order_date
 * @property string|null $supplier_order_date
 * @property string|null $supplier_order_transaction_date
 * @property string|null $supplier_order_confirmation_date
 * @property string|null $expected_order_date
 *
 * @property float|null $opportunity_amount
 * @property string|null $opportunity_amount_currency_code
 * @property float|null $purchase_price
 * @property string|null $purchase_price_currency_code
 * @property float|null $list_price
 * @property string|null $list_price_currency_code
 * @property float|null $estimated_upsell_amount
 * @property string|null $estimated_upsell_amount_currency_code
 * @property float|null $margin_value
 *
 * @property string|null $account_manager_name
 * @property string|null $service_level_agreement_id
 * @property string|null $sale_unit_name
 * @property string|null $competition_name
 * @property string|null $drop_in // ?
 * @property string|null $lead_source_name
 *
 * @property bool|null $has_higher_sla
 * @property bool|null $is_multi_year
 * @property bool|null $has_additional_hardware
 * @property bool|null $has_service_credits
 *
 * @property string|null $remarks
 * @property string|null $notes
 * @property string|null $personal_rating
 * @property int|null $ranking
 *
 * @property string|null $sale_action_name // {"Preparation", "Special Bid Required", "Quote Ready", "Customer Contact", "Customer Order OK", "PO Placed", "Processed on BC", "Closed"}
 *
 * @property ContractType|null $contractType
 * @property Company|null $primaryAccount
 * @property Contact|null $primaryAccountContact
 * @property User|null $accountManager
 * @property Collection<OpportunitySupplier>|OpportunitySupplier[] $opportunitySuppliers
 * @property Collection<Address>|Address[] $addresses
 * @property Collection<Contact>|Contact[] $contacts
 *
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property WorldwideQuote|null $worldwideQuote
 */
class Opportunity extends Model implements SearchableEntity
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function primaryAccount(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function primaryAccountContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opportunitySuppliers(): HasMany
    {
        return $this->hasMany(OpportunitySupplier::class);
    }

    public function worldwideQuote(): HasOne
    {
        return $this->hasOne(WorldwideQuote::class);
    }

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class)->withPivot('is_default');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withPivot('is_default');
    }

    public function toSearchArray(): array
    {
        return [
            'primary_account_name' => transform($this->primaryAccount, fn(Company $account) => $account->name),
            'project_name' => $this->project_name,
            'contract_type' => $this->contractType->type_short_name,
            'opportunity_start_date' => $this->opportunity_start_date,
            'opportunity_end_date' => $this->opportunity_end_date,
            'opportunity_amount' => (string)$this->opportunity_amount,
        ];
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }
}

