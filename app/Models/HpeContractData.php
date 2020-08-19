<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HpeContractData extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'hpe_contract_file_id',
        'amp_id',
        'support_account_reference',
        'contract_number',
        'order_authorization',
        'contract_start_date',
        'contract_end_date',
        'price',
        'product_number',
        'serial_number',
        'product_description',
        'product_quantity',
        'asset_type',
        'service_type',
        'service_code',
        'service_description',
        'service_code_2',
        'service_description_2',
        'service_levels',
        'hw_delivery_contact_name',
        'hw_delivery_contact_phone',
        'sw_delivery_contact_name',
        'sw_delivery_contact_phone',
        'pr_support_contact_name',
        'pr_support_contact_phone',
        'customer_name',
        'customer_address',
        'customer_city',
        'customer_post_code',
        'customer_state_code',
        'support_start_date',
        'support_end_date',
        'is_selected',
    ];

    protected $dates = [
        'support_start_date',
        'support_end_date',
        'contract_start_date',
        'contract_end_date'
    ];

    protected $casts = [
        'support_start_date' => 'datetime:Y-m-d',
        'support_end_date' => 'datetime:Y-m-d',
        'contract_start_date' => 'datetime:Y-m-d',
        'contract_end_date' => 'datetime:Y-m-d',
    ];

    public function hpeContractFile(): BelongsTo
    {
        return $this->belongsTo(HpeContractFile::class)->withDefault();
    }
}
