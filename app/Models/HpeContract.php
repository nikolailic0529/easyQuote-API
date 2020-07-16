<?php

namespace App\Models;

use App\Casts\HpeContactCast;
use App\Casts\HpeServicesCast;
use App\Models\QuoteTemplate\HpeContractTemplate;
use App\Traits\{Uuid, Activatable, BelongsToCompany, BelongsToCountry, BelongsToUser, Completable, Submittable, Auth\Multitenantable};
use Illuminate\Database\Eloquent\{Model, SoftDeletes, Relations\BelongsTo, Relations\HasMany};
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use App\DTO\HpeContractContact;
use App\Models\Customer\Customer;
use App\Models\Quote\Contract;
use App\Scopes\ContractTypeScope;
use App\Traits\Search\Searchable;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property HpeContractContact $hw_delivery_contact
 * @property HpeContractContact $sw_delivery_contact
 * @property HpeContractContact $pr_support_contact
 * @property HpeContractContact $entitled_party_contact
 * @property HpeContractContact $end_customer_contact
 * @property HpeContractContact $sold_contact
 * @property HpeContractContact $bill_contact
 */
class HpeContract extends Model
{
    use Uuid,
        BelongsToCompany,
        BelongsToUser,
        BelongsToCountry,
        SoftDeletes,
        Multitenantable,
        Completable,
        HasRelationships,
        Activatable,
        Submittable;

    protected $fillable = [
        'user_id',
        'quote_template_id',
        'company_id',
        'country_id',

        'amp_id',
        'support_account_reference',
        'orders_authorization',

        'contract_numbers',
        'services',

        'customer_name',
        'customer_address',
        'customer_city',
        'customer_post_code',
        'customer_country_code',

        'purchase_order_no',
        'hpe_sales_order_no',
        'purchase_order_date',

        'hw_delivery_contact',
        'sw_delivery_contact',
        'pr_support_contact',
        'entitled_party_contact',
        'end_customer_contact',

        'sold_contact',
        'bill_contact',

        'last_drafted_step',
        'completeness',
        'checkbox_status',
        'contract_date'
    ];

    protected $dates = [
        'purchase_order_date', 'contract_date'
    ];

    protected $casts = [
        'hw_delivery_contact'       => HpeContactCast::class,
        'sw_delivery_contact'       => HpeContactCast::class,
        'pr_support_contact'        => HpeContactCast::class,
        'entitled_party_contact'    => HpeContactCast::class,
        'end_customer_contact'      => HpeContactCast::class,
        'sold_contact'              => HpeContactCast::class,
        'bill_contact'              => HpeContactCast::class,

        'services'                  => HpeServicesCast::class,

        'contract_numbers'          => 'array',
        'orders_authorization'      => 'array',
        'checkbox_status'           => 'array'
    ];

    public function hpeContractTemplate(): BelongsTo
    {
        return $this->belongsTo(HpeContractTemplate::class, 'quote_template_id')->withDefault();
    }

    public function hpeContractFile(): BelongsTo
    {
        return $this->belongsTo(HpeContractFile::class)->withDefault();
    }

    public function hpeContractData(): HasMany
    {
        return $this->hasMany(HpeContractData::class, 'hpe_contract_file_id', 'hpe_contract_file_id');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class)->withoutGlobalScope(ContractTypeScope::class)->withDefault();
    }

    public static function getCompletenessDictionary()
    {
        return __('hpecontract.stages');
    }
}
