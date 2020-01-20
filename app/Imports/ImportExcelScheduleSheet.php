<?php

namespace App\Imports;

use App\Models\QuoteFile\QuoteFile;
use Maatwebsite\Excel\{
    Row,
    Concerns\OnEachRow,
    Concerns\WithEvents,
    Concerns\WithChunkReading
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportExcelScheduleSheet implements OnEachRow, WithEvents, WithChunkReading
{
    const DATE_REGEXP = '/((?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:[0-9][0-9]))[\.\/]\d{2,4})/';

    const PRICE_REGEXP = '/((\p{Sc})?[ ]?(?<price>([\d]+[ ]?,?)?[,\.]?\d+[,\.]?\d+))/';

    /**
     * QuoteFile Model Instance
     *
     * @var QuoteFile
     */
    protected $quoteFile;

    /**
     * Payment Periods
     *
     * @var array
     */
    protected $matched = [];

    public function __construct(QuoteFile $quoteFile)
    {
        $this->quoteFile = $quoteFile;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray(null, true);

        if ($this->hasMatched(['from', 'to', 'price'])) {
            return;
        }

        if (!$this->hasMatched(['from'])) {
            if (preg_grep('/Support Account Reference.*/i', $row) && $this->hasDates($row)) {
                $this->matched['from'] = $row;
            };
            return;
        }

        if (!$this->hasMatched(['to']) && $this->hasDates($row)) {
            $this->matched['to'] = $row;
            return;
        }

        if (!$this->hasMatched(['price'])) {

            if (preg_grep('/Reseller cost.*/i', $row)) {
                $this->matched['price'] = $row;
            }
            return;
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {
                if (!$this->hasMatched(['from', 'to', 'price'])) {
                    $this->quoteFile->setException(QFNS_01, 'QFNS_01');
                    $this->quoteFile->throwExceptionIfExists();
                }

                $this->createScheduleData();
            }
        ];
    }

    protected function createScheduleData()
    {
        $user_id = $this->quoteFile->user_id;
        $value = $this->handleScheduleData();

        return $this->quoteFile->scheduleData()->create(compact('user_id', 'value'));
    }

    protected function hasMatched(array $flags)
    {
        return collect($this->matched)->has($flags);
    }

    private function handleScheduleData()
    {
        $schedule = [];
        $fromDates = $this->filterDates($this->matched['from']);
        $toDates = $this->filterDates($this->matched['to']);
        $prices = $this->filterPrices($this->matched['price']);

        $schedule = $fromDates->map(function ($from, $key) use ($toDates, $prices) {
            $to = $toDates[$key];
            $price = $prices[$key];

            return compact('from', 'to', 'price');
        })->toArray();

        return $schedule;
    }

    private function hasDates(array &$row)
    {
        $row = collect($row)->transform(function ($value) {
            if (is_int($value)) {
                return Date::excelToDateTimeObject($value)->format('d/m/Y');
            }

            return $value;
        })->toArray();

        return (bool) preg_grep(static::DATE_REGEXP, $row);
    }

    private function filterDates(array $array)
    {
        return collect($array)->filter(function ($value) {
            return (bool) preg_match(static::DATE_REGEXP, $value);
        })->values();
    }

    private function filterPrices(array $array)
    {
        return collect($array)->filter(function ($value) {
            return (bool) preg_match(static::PRICE_REGEXP, $value);
        })->values();
    }
}
