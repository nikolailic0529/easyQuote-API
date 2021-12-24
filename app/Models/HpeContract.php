<?php

namespace App\Models;

use App\Casts\{HpeContactCast, HpeServicesCast};
use App\Contracts\SearchableEntity;
use App\DTO\HpeContractContact;
use App\Models\Template\HpeContractTemplate;
use App\Traits\{Activatable,
    Auth\Multitenantable,
    BelongsToCompany,
    BelongsToCountry,
    BelongsToUser,
    Completable,
    Submittable,
    Uuid};
use App\Traits\Activity\LogsActivity;
use App\Traits\Search\Searchable;
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany, SoftDeletes};
use Illuminate\Support\Collection;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string|null $contract_number
 * @property int|null $sequence_number
 * @property HpeContractContact $hw_delivery_contact
 * @property HpeContractContact $sw_delivery_contact
 * @property HpeContractContact $pr_support_contact
 * @property HpeContractContact $entitled_party_contact
 * @property HpeContractContact $end_customer_contact
 * @property HpeContractContact $sold_contact
 * @property HpeContractContact $bill_contact
 * @property Collection $services
 * @property HpeContractTemplate|null $hpeContractTemplate
 * @property string|null $last_drafted_step
 * @property string|null $submitted_at
 * @property string|null $activated_at
 *
 * @property-read HpeContractFile $hpeContractFile
 */
class HpeContract extends Model implements SearchableEntity
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
        'contract_date',
    ];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

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
        'contract_date',
    ];

    protected $dates = [
        'purchase_order_date', 'contract_date',
    ];

    protected $casts = [
        'hw_delivery_contact' => HpeContactCast::class,
        'sw_delivery_contact' => HpeContactCast::class,
        'pr_support_contact' => HpeContactCast::class,
        'entitled_party_contact' => HpeContactCast::class,
        'end_customer_contact' => HpeContactCast::class,
        'sold_contact' => HpeContactCast::class,
        'bill_contact' => HpeContactCast::class,

        'services' => HpeServicesCast::class,

        'contract_numbers' => 'array',
        'orders_authorization' => 'array',
        'checkbox_status' => 'array',
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

    public function getCompletenessDictionary()
    {
        return __('hpecontract.stages');
    }

    public function toSearchArray(): array
    {
        $this->loadMissing(
            'company:id,name',
            'user:id,first_name,last_name'
        );

        return [
            'company_name' => $this->company->name,
            'contract_number' => $this->contract_number,
            'customer_name' => $this->sold_contact->org_name,
            'customer_rfq' => $this->contract_number,
            'customer_valid_until' => null,
            'customer_support_start' => null,
            'customer_support_end' => null,
            'user_fullname' => optional($this->user)->fullname,
            'created_at' => $this->created_at,
            'is_submitted' => !is_null($this->submitted_at),
        ];
    }

    public function getItemNameAttribute()
    {
        return "HPE Contract ({$this->contract_number})";
    }

    public function getSearchIndex(): string
    {
        return 'contracts';
    }

    public function getSearchType()
    {
        return 'contracts';
    }
}
