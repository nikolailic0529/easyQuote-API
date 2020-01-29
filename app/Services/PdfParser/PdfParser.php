<?php

namespace App\Services\PdfParser;

use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumnRepository;
use Illuminate\Support\Collection;
use Smalot\PdfParser\Parser as SmalotPdfParser;
use Spatie\PdfToText\Pdf as SpatiePdfParser;
use Illuminate\Support\LazyCollection;
use Storage, Str, Arr;

class PdfParser implements PdfParserInterface
{
    /** @var \App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface */
    protected $importableColumn;

    /** @var \Smalot\PdfParser\Parser */
    protected $smalotPdfParser;

    /** @var \Spatie\PdfToText\Pdf */
    protected $spatiePdfParser;

    /** @var array|null */
    protected static $columnNames;

    public function __construct(
        ImportableColumnRepository $importableColumn,
        SmalotPdfParser $smalotPdfParser,
        SpatiePdfParser $spatiePdfParser
    ) {
        $this->importableColumn = $importableColumn;
        $this->smalotPdfParser = $smalotPdfParser;
        $this->spatiePdfParser = $spatiePdfParser;
    }

    public function getText(string $path, bool $storage = true)
    {
        $filePath = $storage ? Storage::path($path) : $path;

        $count = $this->countPages($path, $storage);

        $rawPages = collect()->times($count, function ($page) use ($filePath) {
            $content = $this->getPageContent($filePath, $page);
            return compact('page', 'content');
        });

        return $rawPages->toArray();
    }

    public function getPageContent(string $path, int $page)
    {
        try {
            return $this->spatiePdfParser->setPdf($path)
                ->setOptions(['layout', 'eol unix', 'table', "f {$page}", "l {$page}"])
                ->text();
        } catch (\Exception $e) {
            return $this->spatiePdfParser->setPdf($path)
                ->setOptions(['layout', 'eol unix', "f {$page}", "l {$page}"])
                ->text();
        }
    }

    public function parse(array $array)
    {
        $pages = LazyCollection::make(function () use ($array) {
            foreach ($array as $page) {
                yield ['page' => $page['page'], 'content' => Storage::get($page['file_path'])];
            }
        });

        $pagesData = $pages->map(function ($page, $key) {
            ['page' => $page, 'content' => $content] = $page;

            $rows = [];

            if (blank($matches = $this->fetchPricePage($content))) {
                return compact('page', 'rows');
            }

            /**
             * Resetting Coverage Periods for rows with the same Product Number
             */
            $matches = $this->resetCoveragePeriods($matches);

            $rows = $this->mapColumns($matches);

            return compact('page', 'rows');
        });

        return $pagesData->toArray();
    }

    public function parseSchedule(array $array)
    {
        $filePath = $array['file_path'];

        $content = Storage::get($filePath);

        $matches = $this->findPaymentDates($content) + ['payments' => $this->findTotalPayments($content)];
        $matches = $this->filterScheduleMatches($matches);

        if (!$matches->has(PdfOptions::SCHEDULE_MATCHES)) {
            error_abort(QFNS_01, 'QFNS_01', 422);
        }

        $payments = $matches->get('payments', []);
        $paymentDatesFirstLine = head($matches->get('payment_dates', []));
        $paymentDatesOptions = $matches->get('payment_dates_options', []);

        $colsMapping = $this->scheduleColsMapping($paymentDatesFirstLine);

        $paymentOptions = $this->filterScheduleDates($paymentDatesFirstLine, $paymentDatesOptions, $colsMapping);

        $payments = $this->filterSchedulePayments($payments, $colsMapping);

        $schedule = $this->mapPayments($payments, $paymentOptions);

        return $schedule;
    }

    public function countPages(string $path, bool $storage = true)
    {
        $filePath = $storage ? Storage::path($path) : $path;

        $document = $this->smalotPdfParser->parseFile($filePath);

        return count($document->getPages());
    }

    protected function getColumnNames(): array
    {
        if (isset(static::$columnNames)) {
            return static::$columnNames;
        }

        return static::$columnNames = $this->importableColumn->allNames();
    }

    private function fetchPricePage(string $content): array
    {
        $lines = [];

        $matches = collect([PdfOptions::REGEXP_PRICE_LINES_01, PdfOptions::REGEXP_PRICE_LINES_02])
            ->contains(function ($regexp) use ($content, &$lines) {
                return preg_match_all($regexp, $content, $lines, PREG_UNMATCHED_AS_NULL, 0) > 0;
            });

        if (!$matches) {
            return [];
        }

        /**
         * We are finding Service Agreement ID on each page and assign it to all rows on the page.
         */
        $count = count(head($lines));
        $sid = $this->findSID($content);

        $searchable = array_fill(0, $count, $sid);
        $lines += compact('searchable');

        return Arr::only($lines, $this->getColumnNames());
    }

    private function findSID(string $content)
    {
        preg_match(PdfOptions::REGEXP_PRICE_SID, $content, $matches, PREG_UNMATCHED_AS_NULL, 0);

        return optional($matches)[1];
    }

    private function findPaymentDates(string $content): array
    {
        preg_match_all(PdfOptions::REGEXP_SCHEDULE_PAYMENTS, $content, $matches, PREG_UNMATCHED_AS_NULL, 0);

        return $matches;
    }

    private function findTotalPayments(string $content): array
    {
        $payments = [];

        if (preg_match_all('/Total\s*/i', $content, $totals, PREG_SET_ORDER)) {
            $totals = Str::afterLast($content, head(last($totals)));
            preg_match_all(PdfOptions::REGEXP_SCHEDULE_PRICE, $totals, $payments, PREG_UNMATCHED_AS_NULL, 0);
        };

        return data_get($payments, 'price', []);
    }

    private function filterScheduleMatches(array $matches): Collection
    {
        return collect($matches)->only(PdfOptions::SCHEDULE_MATCHES)
            ->transform(function ($item) {
                return array_filter($item);
            })->reject(function ($item) {
                return empty($item);
            });
    }

    private function mapPayments(Collection $payments, array $paymentOptions): array
    {
        return $payments->map(function ($payment, $key) use ($paymentOptions) {
            return [
                'from' => data_get($paymentOptions, "0.{$key}"),
                'to' => data_get($paymentOptions, "1.{$key}"),
                'price' => $payment
            ];
        })->toArray();
    }

    private function mapColumns(array $columns): array
    {
        $rows = [];

        foreach ($columns as $column => $values) {
            foreach ($values as $key => $value) {
                $rows[$key][$column] = $this->handleColumnValue($value);
            }
        }

        return $rows;
    }

    private function filterSchedulePayments(array $payments, array $colsMapping): Collection
    {
        return collect($payments)->filter(function ($payment, $key) use ($colsMapping) {
            return data_get($colsMapping, $key);
        })->transform(function ($payment) {
            return Str::price($payment, false, true);
        })->values();
    }

    private function filterScheduleDates(string $firstLine, array $optionsLine, array $colsMapping): array
    {
        // Payment dates.
        $dates = collect();

        preg_match_all(PdfOptions::REGEXP_SCHEDULE_DATE, $firstLine, $matches);
        $dates->push($matches['date']);

        $colsCount = count(array_filter($colsMapping));

        $options = collect($optionsLine)
            ->transform(function ($option) {
                preg_match_all(PdfOptions::REGEXP_SCHEDULE_DATE, $option, $matches);

                return data_get($matches, 'date', []);
            })
            ->filter(function ($dates) use ($colsCount) {
                return count($dates) === $colsCount;
            });

        return $dates->merge($options)->toArray();
    }

    private function scheduleColsMapping(string $firstLine): array
    {
        preg_match_all(PdfOptions::REGEXP_SCHEDULE_COLUMNS, $firstLine, $matches, PREG_UNMATCHED_AS_NULL, 0);

        return collect($matches['cols'])->filter()->transform(function ($value) {
            return (bool) preg_match(PdfOptions::REGEXP_SCHEDULE_DATE, $value);
        })
            ->values()->toArray();
    }

    private function handleColumnValue($value)
    {
        return is_string($value)
            ? trim(preg_replace('/[\s\t]+/', ' ', $value))
            : $value;
    }

    private function resetCoveragePeriods(array $array): array
    {
        $productNo = $array['product_no'];
        $periodFrom = $array['date_from'];
        $periodTo = $array['date_to'];

        foreach ($periodFrom as $key => $row) {
            $nextKey = $key + 1;

            if (!(isset($periodFrom[$key]) &&
                isset($periodFrom[$nextKey]) &&
                !isset($periodTo[$key]) &&
                !isset($periodTo[$nextKey]) &&
                $productNo[$key] === $productNo[$nextKey] &&
                $periodFrom[$key] !== $periodFrom[$nextKey])) {
                continue;
            }

            $periodTo[$key] = $periodFrom[$key];
            $periodFrom[$key] = null;
        }

        $resettedPeriods = [
            'date_from' => $periodFrom,
            'date_to' => $periodTo
        ];

        return array_merge($array, $resettedPeriods);
    }
}
