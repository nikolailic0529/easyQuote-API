<?php

namespace App\Domain\Rescue\Concerns;

use App\Domain\Rescue\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCustomer
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault()->withTrashed();
    }

    public function scopeRfq(Builder $query, string $rfq): Builder
    {
        return $query->whereHas('customer', fn ($query) => $query->whereRfq($rfq));
    }

    public function getRfqNumberAttribute()
    {
        return optional($this->customer)->rfq;
    }
}
