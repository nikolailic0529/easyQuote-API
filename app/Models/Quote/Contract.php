<?php

namespace App\Models\Quote;

use App\Models\HpeContract;
use App\Scopes\{
    ContractTypeScope,
    NonVersionScope
};
use App\Traits\{
    BelongsToQuote,
    NotifiableModel,
    Quote\HasVersions
};
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class Contract extends BaseQuote
{
    const REG_CUSTOMER_RFQ_PREFIX = 'CQ';

    const QB_CUSTOMER_RFQ_PREFIX = 'CT';

    use HasVersions, BelongsToQuote, NotifiableModel;

    protected $attributes = [
        'document_type' => Q_TYPE_CONTRACT
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new NonVersionScope);
        static::addGlobalScope(new ContractTypeScope);
    }

    public function toSearchArray()
    {
        return [
            'company_name'              => optional($this->company)->name,
            'contract_number'           => $this->contract_number,
            'customer_name'             => optional($this->customer)->name,
            'customer_rfq'              => optional($this->customer)->rfq,
            'customer_valid_until'      => optional($this->customer)->valid_until,
            'customer_support_start'    => optional($this->customer)->support_start,
            'customer_support_end'      => optional($this->customer)->support_end,
            'user_fullname'             => optional($this->user)->fullname,
            'created_at'                => $this->created_at,
        ];
    }

    public function getItemNameAttribute()
    {
        return "Contract ({$this->contract_number})";
    }

    public function getContractNumberAttribute()
    {
        if ($this->document_type === Q_TYPE_HPE_CONTRACT) {
            return $this->hpe_contract_number;
        }

        return (string) Str::of($this->customer->rfq)->replaceFirst(static::REG_CUSTOMER_RFQ_PREFIX, static::QB_CUSTOMER_RFQ_PREFIX);
    }

    public function getCompletenessDictionary()
    {
        if ($this->document_type === Q_TYPE_HPE_CONTRACT) {
            return HpeContract::modelCompleteness();
        }

        return parent::getCompletenessDictionary();
    }
}
