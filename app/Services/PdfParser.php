<?php namespace App\Services;

use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumnRepository;
use Smalot\PdfParser\Parser as SmalotPdfParser;
use Illuminate\Support\LazyCollection;
use Storage;

class PdfParser implements PdfParserInterface
{
    protected $importableColumn;

    protected $smalotPdfParser;

    public function __construct(
        ImportableColumnRepository $importableColumn,
        SmalotPdfParser $smalotPdfParser
    ) {
        $this->importableColumn = $importableColumn;
        $this->smalotPdfParser = $smalotPdfParser;
    }

    public function getText(string $path)
    {
        $filePath = Storage::path($path);

        $document = $this->smalotPdfParser->parseFile($filePath);

        $rawPages = collect();

        collect($document->getPages())->each(function ($page, $key) use ($rawPages) {
            $text = str_replace("\0", "", $page->getText());

            $rawPages->push([
                'page' => ++$key,
                'content' => $text
            ]);
        });

        return $rawPages->toArray();
    }

    public function parse(array $array)
    {
        $regexpColumns = $this->importableColumn->allColumnsRegs();

        $regexp = $regexpColumns->implode('');
        $regexp = "/^{$regexp}$/mu";

        $pages = LazyCollection::make(function () use ($array) {
            foreach ($array as $page) {
                yield ['page' => $page['page'], 'content' => Storage::get($page['file_path'])];
            }
        });

        $pagesData = $pages->map(function ($page, $key) use ($regexp) {
            ['page' => $page, 'content' => $content] = $page;

            preg_match_all($regexp, $content, $matches, PREG_UNMATCHED_AS_NULL);

            $columnsAliases = $this->importableColumn->allNames();

            $matches = collect($matches)->only($columnsAliases)->toArray();

            /**
             * Resetting Coverage Periods for rows with the same Product Number
             */
            $matches = $this->resetCoveragePeriods($matches);

            $rows = [];

            foreach ($matches as $column => $values) {
                foreach ($values as $key => $value) {
                    $rows[$key][$column] = $value;
                }
            }

            return compact('page', 'rows');
        });

        return $pagesData->toArray();
    }

    public function parseSchedule(array $array)
    {
        $lastPage = collect($array)->last();
        $filePath = $lastPage['file_path'];

        $content = Storage::get($filePath);

        // dd($content);

        $priceGroup = '((\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,2})))';
        $dateGroup = '(?<date>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{4})';

        $regexp = "/(?<payment>^[ ]?(?<account>\w[\w -]+?)(\s+?{$priceGroup})+$)|(?<payment_dates>(?:system handle)(?:(?:[^\S\r\n]+)?{$dateGroup})+)|(^(?:(?:\n?)(?<payment_dates_options>((?:[^\S\r\n]+)?\g'date')+)))/mi";

        preg_match_all($regexp, $content, $matches, PREG_UNMATCHED_AS_NULL);

        $matches = collect($matches)->only('payment', 'account', 'payment_dates', 'payment_dates_options')->map(function ($item) {
            return collect($item)->filter(function ($value) {
                return !is_null($value);
            })->map(function ($value) {
                return trim($value);
            })->values();
        });

        if($matches['account']->isEmpty() || $matches['payment']->isEmpty() || $matches['payment_dates']->isEmpty() || $matches['payment_dates_options']->isEmpty()) {
            throw new \ErrorException(__('parser.not_schedule_exception'));
        }

        $account = str_replace(' ', '', $matches['account'][0]);
        $paymentLines = $matches['payment'];
        $paymentDatesStandard = $matches['payment_dates'][0];
        $paymentDatesOptions = $matches['payment_dates_options'];

        // Payment Lines
        $paymentsRegexp = "/{$priceGroup}/";
        $paymentLines = collect($paymentLines)->map(function ($paymentLine) use ($paymentsRegexp) {
            preg_match_all($paymentsRegexp, $paymentLine, $matches);
            return $matches['price'];
        });

        // Payment dates options
        $datesRegexp = "/{$dateGroup}/";
        $paymentOptions = [];

        preg_match_all($datesRegexp, $paymentDatesStandard, $matches);
        $paymentOptions[] = $matches['date'];

        foreach ($paymentDatesOptions as $paymentOption) {
            preg_match_all($datesRegexp, $paymentOption, $matches);
            $paymentOptions[] = $matches['date'];
        }

        $paymentSchedule = [];

        $paymentScheduleHeader = [];
        foreach ($paymentOptions as $key => $value) {
            $headerRow = [];

            if($key === 0) {
                $headerRow[] = __('quote.system_handle');
            } else {
                $headerRow[] = null;
            }

            $paymentScheduleHeader[] = array_merge($headerRow, $value);
        }

        $paymentScheduleRows = [];
        foreach ($paymentLines as $key => $value) {
            $row = [];

            if($key === 0) {
                $row[] = $account;
            } else {
                $row[] = null;
            }

            $paymentScheduleRows[] = array_merge($row, $value);
        }

        $paymentSchedule = ['header' => $paymentScheduleHeader, 'rows' => $paymentScheduleRows];

        return $paymentSchedule;
    }

    public function countPages(string $path)
    {
        $filePath = Storage::path($path);

        $document = $this->smalotPdfParser->parseFile($filePath);

        return count($document->getPages());
    }

    private function resetCoveragePeriods(array $array)
    {
        $productNo = $array['product_no'];
        $periodFrom = $array['date_from'];
        $periodTo = $array['date_to'];

        foreach ($periodTo as $key => $row) {
            $nextKey = $key + 1;

            if(
                !isset($periodTo[$nextKey]) || is_null($row) ||
                is_null($periodTo[$nextKey]) || !is_null($periodFrom[$key]) ||
                $productNo[$key] !== $productNo[$nextKey]
            ) {
                continue;
            };

            $periodFrom[$key] = $periodTo[$key];
            $periodTo[$key] = null;
        }

        $resettedPeriods = [
            'date_from' => $periodFrom,
            'date_to' => $periodTo
        ];

        return array_merge($array, $resettedPeriods);
    }
}
