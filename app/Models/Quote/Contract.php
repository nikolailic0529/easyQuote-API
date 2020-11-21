<?php

namespace App\Models\Quote;

use App\Contracts\ReindexQuery;
use App\Models\HpeContract;
use App\Scopes\{
    AnyContractTypeScope,
    ContractTypeScope,
    NonVersionScope
};
use App\Traits\{
    BelongsToQuote,
    NotifiableModel,
    Quote\HasVersions
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class Contract extends BaseQuote implements ReindexQuery
{
    const REG_CUSTOMER_RFQ_PREFIX = 'CQ';

    const QB_CUSTOMER_RFQ_PREFIX = 'CT';

    use HasVersions, BelongsToQuote, NotifiableModel;

    protected $attributes = [
        'document_type' => Q_TYPE_CONTRACT
    ];

    protected static function booted()
    {
        static::addGlobalScope(new NonVersionScope);
        static::addGlobalScope(new ContractTypeScope);
    }

    public function toSearchArray()
    {
        $this->loadMissing(
            'customer:id,rfq,valid_until,name,support_start,support_end,valid_until',
            'company:id,name',
            'user:id,first_name,last_name'
        );

        $customerName = $this->document_type === Q_TYPE_HPE_CONTRACT ? $this->hpe_contract_customer_name : $this->customer->name;

        return [
            'company_name'           => $this->company->name,
            'contract_number'        => $this->contract_number,
            'customer_name'          => $customerName,
            'customer_rfq'           => $this->customer->rfq,
            'customer_valid_until'   => $this->customer->valid_until,
            'customer_support_start' => $this->customer->support_start,
            'customer_support_end'   => $this->customer->support_end,
            'user_fullname'          => optional($this->user)->fullname,
            'created_at'             => optional($this->created_at)->format(config('date.format')),
        ];
    }

    public static function reindexQuery(): Builder
    {
        return static::query()->withoutGlobalScope(ContractTypeScope::class)->withGlobalScope(AnyContractTypeScope::class, new AnyContractTypeScope);
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
