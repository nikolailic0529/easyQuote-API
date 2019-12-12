<?php

namespace App\Models\Customer;

use App\Models\UuidModel;
use App\Traits\{
    BelongsToAddresses,
    BelongsToContacts,
    HasAddressTypes,
    HasContactTypes,
    Submittable,
    Quote\HasQuotes
};
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends UuidModel
{
    use BelongsToAddresses, HasAddressTypes, BelongsToContacts, HasContactTypes, Submittable, HasQuotes, SoftDeletes;

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
        'service_levels'
    ];

    protected $hidden = [
        'updated_at', 'deleted_at'
    ];

    protected $casts = [
        'service_levels' => 'collection'
    ];

    protected $dates = ['valid_until', 'support_start', 'support_end'];

    public function getSupportStartAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }

    public function getSupportEndAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }

    public function getValidUntilAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }

    public function getSupportStartDateAttribute()
    {
        return carbon_format($this->attributes['support_start'], config('date.format'));
    }

    public function getSupportEndDateAttribute()
    {
        return carbon_format($this->attributes['support_end'], config('date.format'));
    }

    public function getValidUntilDateAttribute()
    {
        return carbon_format($this->attributes['valid_until'], config('date.format'));
    }

    public function getCoveragePeriodAttribute()
    {
        return "{$this->support_start_date} to {$this->support_end_date}";
    }

    public function getServiceLevelsFormattedAttribute()
    {
        return collect($this->service_levels)->toString('service_level');
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
        $this->attributes['support_start'] = $value;
    }

    public function setSupportEndDateAttribute($value)
    {
        $this->attributes['support_end'] = $value;
    }

    public function setQuotationValidUntilAttribute($value)
    {
        $this->attributes['valid_until'] = $value;
    }

    public function scopeNotInUse($query)
    {
        return $query->drafted()->doesntHave('quotes');
    }
}
