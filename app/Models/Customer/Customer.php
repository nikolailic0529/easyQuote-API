<?php

namespace App\Models\Customer;

use App\Models\Address;
use App\Traits\{
    BelongsToAddresses,
    BelongsToContacts,
    BelongsToCountry,
    HasAddressTypes,
    HasContactTypes,
    Submittable,
    Quote\HasQuotes,
    Activity\LogsActivity,
    Migratable,
    Uuid
};
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Staudenmeir\EloquentHasManyDeep\{
    HasManyDeep,
    HasOneDeep,
    HasRelationships,
};

class Customer extends Model
{
    public const S4_SOURCE = 'S4', EQ_SOURCE = 'easyQuote';

    use Uuid,
        BelongsToAddresses,
        HasAddressTypes,
        BelongsToContacts,
        BelongsToCountry,
        HasContactTypes,
        Submittable,
        HasQuotes,
        SoftDeletes,
        LogsActivity,
        Migratable,
        HasRelationships;

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
        'sequence_number',
        'source',
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

    protected static $logAttributes = [
        'customer_name',
        'support_start_date',
        'support_end_date',
        'rfq_number',
        'quotation_valid_until',
        'invoicing_terms',
        'service_levels:service_levels_formatted'
    ];

    protected static $recordEvents = ['deleted'];

    public function addresses(): MorphToMany
    {
        return $this->morphToMany(Address::class, 'addressable')->withTrashed();
    }

    public function locations(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->addresses(), (new Address)->location());
    }

    public function equipmentLocation(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations($this->addresses(), (new Address)->location())->where('addresses.address_type', 'Equipment')->withDefault();
    }

    public function getSupportStartAttribute($value)
    {
        return carbon_format($value, config('date.format_time'));
    }

    public function getSupportEndAttribute($value)
    {
        return carbon_format($value, config('date.format_time'));
    }

    public function getValidUntilAttribute($value)
    {
        return carbon_format($value, config('date.format_time'));
    }

    public function getValidUntilAsDateAttribute()
    {
        $value = $this->getAttributeFromArray('valid_until');
        
        return $value ? $this->asDate($value) : null;
    }

    public function getSupportStartDateAttribute()
    {
        return carbon_format($this->attributes['support_start'], $this->dynamicDateFormat);
    }

    public function getSupportEndDateAttribute()
    {
        return carbon_format($this->attributes['support_end'], $this->dynamicDateFormat);
    }

    public function getValidUntilDateAttribute()
    {
        return carbon_format($this->attributes['valid_until'], $this->dynamicDateFormat);
    }

    public function getValidUntilUiAttribute()
    {
        return carbon_format($this->attributes['valid_until'], config('date.format_ui'));
    }

    public function getQuotationValidUntilAttribute()
    {
        return $this->valid_until_date;
    }

    public function getDynamicDateFormatAttribute()
    {
        return isset(array_flip(['US', 'CA'])[$this->country_code])
            ? config('date.format')
            : config('date.format_eu');
    }

    public function getCoveragePeriodAttribute()
    {
        return "{$this->support_start_date} to {$this->support_end_date}";
    }

    public function getServiceLevelsFormattedAttribute()
    {
        return collect($this->service_levels)->toString('service_level');
    }

    public function getCustomerNameAttribute()
    {
        return $this->name;
    }

    public function getRfqNumberAttribute()
    {
        return $this->rfq;
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

    public function withAppends()
    {
        return $this->makeHidden(['name', 'support_start', 'support_end', 'valid_until', 'rfq'])
            ->append('customer_name', 'rfq_number', 'support_start_date', 'support_end_date', 'quotation_valid_until');
    }

    public function getItemNameAttribute(): string
    {
        return "Customer ({$this->rfq})";
    }

    public function toSearchArray()
    {
        return [
            'name'          => $this->name,
            'rfq'           => $this->rfq,
            'valid_until'   => $this->valid_until_date,
            'support_start' => $this->support_start_date,
            'support_end'   => $this->support_end_date
        ];
    }

    public function toCacheableArray()
    {
        return [
            'name'          => $this->name,
            'rfq'           => $this->rfq,
            'source'        => $this->source,
            'valid_until'   => $this->valid_until_date,
            'support_start' => $this->support_start_date,
            'support_end'   => $this->support_end_date
        ];
    }
}
