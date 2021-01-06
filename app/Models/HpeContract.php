<?php

namespace App\Models;

use App\Casts\{HpeContactCast, HpeServicesCast};
use App\Models\Template\HpeContractTemplate;
use App\Traits\{Uuid, Activatable, BelongsToCompany, BelongsToCountry, BelongsToUser, Completable, Submittable, Auth\Multitenantable};
use Illuminate\Database\Eloquent\{Model, SoftDeletes, Relations\BelongsTo, Relations\HasMany};
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use App\DTO\HpeContractContact;
use App\Models\Quote\Contract;
use App\Scopes\ContractTypeScope;
use App\Traits\Activity\LogsActivity;
use App\Traits\Search\Searchable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property HpeContractContact $hw_delivery_contact
 * @property HpeContractContact $sw_delivery_contact
 * @property HpeContractContact $pr_support_contact
 * @property HpeContractContact $entitled_party_contact
 * @property HpeContractContact $end_customer_contact
 * @property HpeContractContact $sold_contact
 * @property HpeContractContact $bill_contact
 * @property Collection $services
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
        LogsActivity,
        Activatable,
        Searchable,
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

        'additional_notes',

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

    protected static $logAttributes = [
        'amp_id',

        'customer_name',
        'customer_address',
        'customer_city',
        'customer_post_code',
        'customer_country_code',

        'purchase_order_no',
        'hpe_sales_order_no',
        'purchase_order_date',

        'additional_notes',

        'last_drafted_step',
        'completeness',
        'contract_date'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

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

    public function getCompletenessDictionary()
    {
        return __('hpecontract.stages');
    }

    public function toSearchArray()
    {
        $this->loadMissing(
            'company:id,name',
            'user:id,first_name,last_name'
        );

        return [
            'company_name'           => $this->company->name,
            'contract_number'        => $this->contract_number,
            'customer_name'          => $this->sold_contact->org_name,
            'customer_rfq'           => $this->contract_number,
            'customer_valid_until'   => null,
            'customer_support_start' => null,
            'customer_support_end'   => null,
            'user_fullname'          => optional($this->user)->fullname,
            'created_at'             => optional($this->created_at)->format(config('date.format')),
        ];
    }

    public function getItemNameAttribute()
    {
        return "HPE Contract ({$this->contract_number})";
    }

    public function getSearchIndex()
    {
        return 'contracts';
    }

    public function getSearchType()
    {
        return 'contracts';
    }
}
