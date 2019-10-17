<?php namespace App\Imports;

use App\Models\QuoteFile\QuoteFile;
use Maatwebsite\Excel \ {
    Row,
    Concerns\OnEachRow,
    Concerns\WithEvents,
    Concerns\WithChunkReading
};
use Maatwebsite\Excel\Events\AfterSheet;

class ImportExcelScheduleSheet implements OnEachRow, WithEvents, WithChunkReading
{
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

        if($this->hasMatched(['from', 'to', 'price'])) {
            return;
        }

        if(!$this->hasMatched(['from'])) {
            if(preg_grep('/Support Account Reference.*/i', $row) && preg_grep('/((?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{2,4})/', $row)) {
                $this->matched['from'] = $row;
            };
            return;
        }

        if(!$this->hasMatched(['to'])) {
            $this->matched['to'] = $row;
            return;
        }

        if(!$this->hasMatched(['price'])) {
            if(preg_grep('/Reseller cost.*/i', $row)) {
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
                if(!$this->hasMatched(['from', 'to', 'price'])) {
                    $this->quoteFile->setException(__('parser.not_schedule_exception'));
                    throw new \ErrorException(__('parser.not_schedule_exception'));
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

    private function filterDates(array $array)
    {
        return collect($array)->filter(function ($value) {
            return (bool) preg_match('/((?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{2,4})/', $value);
        })->values();
    }

    private function filterPrices(array $array)
    {
        return collect($array)->filter(function ($value) {
            return (bool) preg_match('/((\p{Sc})?[ ]?(?<price>([\d]+[ ]?,?)?[,\.]?\d+[,\.]?\d+))/', $value);
        })->values();
    }
}
