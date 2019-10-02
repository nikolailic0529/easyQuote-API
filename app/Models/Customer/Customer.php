<?php namespace App\Models\Customer;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportableColumn
};
use App\Traits \ {
    HasAddresses,
    HasContacts
};
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class Customer extends UuidModel
{
    use HasAddresses, HasContacts;

    protected $fillable = [
        'name', 'support_start', 'support_end', 'rfq', 'valid_until', 'payment_terms', 'invoicing_terms', 'service_level'
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

    public function handleColumnValue($value, $importableColumn, $isDefaultEnabled = false)
    {
        if(!$importableColumn instanceof ImportableColumn) {
            return $value;
        }

        if($importableColumn->isDateFrom()) {
            return $this->formatDate($value, $this->support_start, $isDefaultEnabled);
        }

        if($importableColumn->isDateTo()) {
            return $this->formatDate($value, $this->support_end, $isDefaultEnabled);
        }

        return trim($value);
    }

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

    public function hardwareAddresses()
    {
        return $this->addresses()->where('address_type', 'Hardware');
    }

    public function softwareAddresses()
    {
        return $this->addresses()->where('address_type', 'Software');
    }

    public function hardwareContacts()
    {
        return $this->contacts()->where('contact_type', 'Hardware');
    }

    public function softwareContacts()
    {
        return $this->contacts()->where('contact_type', 'Software');
    }

    private function formatDate($value, $default, $isDefaultEnabled)
    {
        if($isDefaultEnabled) {
            return $default;
        }

        if(preg_match('/^\d{5}$/', $value)) {
            return Date::excelToDateTimeObject($value)->format($this->dateTimeFormat);
        }

        try {
            $dateTimeValue = preg_replace_callback(
                '/(\d{1,2})\D(\d{1,2})\D(\d{2,4})/',
                function ($matches) {
                    if(isset($matches[0]) && $matches[0] > 12) {
                        return "{$matches[1]}.{$matches[0]}.{$matches[2]}";
                    }
                    return "{$matches[0]}.{$matches[1]}.{$matches[2]}";
                },
                $value
            );
            $dateTimeValue = preg_replace('/[^\w\.\/|\\,]+/', '', $dateTimeValue);

            if(strlen($dateTimeValue) < 4) {
                return $default;
            }

            return Carbon::parse($dateTimeValue)->format($this->dateTimeFormat);
        } catch (\Exception $e) {
            return $value;
        }
    }
}
