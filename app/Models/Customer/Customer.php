<?php

namespace App\Models\Customer;

use App\Models\UuidModel;
use App\Traits\{
    HasAddresses,
    HasContacts,
    Submittable,
    Quote\HasQuotes
};
use Illuminate\Support\Carbon;

class Customer extends UuidModel
{
    use HasAddresses, HasContacts, Submittable, HasQuotes;

    protected $attributes = [
        'support_start' => null,
        'support_end' => null,
        'valid_until' => null
    ];

    protected $fillable = [
        'name',
        'customer_name',
        'support_start',
        'support_start_date',
        'support_end',
        'support_end_date',
        'rfq',
        'rfq_number',
        'valid_until',
        'quotation_valid_until',
        'payment_terms',
        'invoicing_terms',
        'service_level'
    ];

    protected $hidden = [
        'updated_at', 'deleted_at'
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y',
        'valid_until' => 'datetime:d/m/Y',
        'support_start' => 'datetime:d/m/Y',
        'support_end' => 'datetime:d/m/Y'
    ];

    protected $dateTimeFormat = 'd/m/Y';

    public function getSupportStartAttribute()
    {
        return Carbon::parse($this->attributes['support_start'])->format($this->dateTimeFormat);
    }

    public function getSupportEndAttribute()
    {
        return Carbon::parse($this->attributes['support_end'])->format($this->dateTimeFormat);
    }

    public function getValidUntilAttribute()
    {
        return Carbon::parse($this->attributes['valid_until'])->format($this->dateTimeFormat);
    }

    public function getCoveragePeriodAttribute()
    {
        return "{$this->support_start} to {$this->support_end}";
    }

    public function setCustomerNameAttribute($value)
    {
        $this->name = $value;
    }

    public function setRfqNumberAttribute($value)
    {
        $this->rfq = $value;
    }

    public function setSupportStartDateAttribute($value)
    {
        $this->support_start = $value;
    }

    public function setSupportEndDateAttribute($value)
    {
        $this->support_end = $value;
    }

    public function setQuotationValidUntilAttribute($value)
    {
        $this->valid_until = $value;
    }
}
