<?php namespace App\Models\Customer;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportableColumn
};
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class Customer extends UuidModel
{
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

    private function formatDate($value, $default, $isDefaultEnabled)
    {
        if($isDefaultEnabled) {
            return Carbon::parse($default)->format($this->dateTimeFormat);
        }

        if(preg_match('/^\d{5}$/', $value)) {
            return Date::excelToDateTimeObject($value)->format($this->dateTimeFormat);
        }

        try {
            $dateTimeValue = preg_replace('/(\d{1,2})\D(\d{1,2})\D(\d{2,4})/', '${1}.${2}.${3}', $value);

            if(strlen($dateTimeValue) < 4) {
                return Carbon::parse($default)->format($this->dateTimeFormat);
            }

            return Carbon::parse($dateTimeValue)->format($this->dateTimeFormat);
        } catch (\Exception $e) {
            return Carbon::parse($default)->format($this->dateTimeFormat);
        }
    }
}