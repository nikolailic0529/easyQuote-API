<?php

namespace App\Services\PdfParser;

use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumns;
use Illuminate\Support\Collection;
use Smalot\PdfParser\Parser as SmalotPdfParser;
use Spatie\PdfToText\Pdf as SpatiePdfParser;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Storage;

class PdfParser implements PdfParserInterface
{
    const CONTENT_OPTIONS = ['layout', 'eol unix', 'table'];

    const CONTENT_OPTIONS_FB = ['layout', 'eol unix'];

    const DT_FROM = 'date_from', DT_TO = 'date_to';

    protected ImportableColumns $importableColumns;

    protected SmalotPdfParser $smalotPdfParser;

    protected SpatiePdfParser $spatiePdfParser;

    protected static ?array $columnNames = null;

    protected static array $exactDatesMatchesCache = [];

    public function __construct(
        ImportableColumns $importableColumns,
        SmalotPdfParser $smalotPdfParser,
        SpatiePdfParser $spatiePdfParser
    ) {
        $this->importableColumns = $importableColumns;
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
        $pageOptions = ["f {$page}", "l {$page}"];

        try {
            return $this->spatiePdfParser->setPdf($path)
                ->setOptions([...static::CONTENT_OPTIONS, ...$pageOptions])
                ->text();
        } catch (\Exception $e) {
            return $this->spatiePdfParser->setPdf($path)
                ->setOptions([...static::CONTENT_OPTIONS_FB, ...$pageOptions])
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

        $attributes = [];

        $pages = $pages->map(function ($page, $key) use (&$attributes) {
            ['page' => $page, 'content' => $content] = $page;

            $rows = [];

            $attributes = $this->findPriceAttributes($content, $attributes);

            if (blank($matches = $this->fetchPricePage($content))) {
                return compact('page', 'rows');
            }

            static::cacheExactDatesMatches($matches);

            /**
             * Resetting Coverage Periods for rows with the same Product Number
             */
            $matches = $this->resetCoveragePeriods($matches);

            $rows = $this->mapColumns($matches);

            return compact('page', 'rows');
        });

        $pages = collect($pages->all())
            ->map(function ($page) {
                $rows = collect(static::setExactDatesMatches($page['rows']))
                    ->map(fn ($row) => Arr::set($row, PdfOptions::SYSTEM_HEADER_ONE_PAY, static::seekColumnsForOnePay($row)))
                    ->toArray();

                return Arr::set($page, 'rows', $rows);
            })
            ->toArray();

        return compact('pages', 'attributes');
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

        return static::$columnNames = $this->importableColumns->allNames();
    }

    private function fetchPricePage(string $content): array
    {
        $matches = collect([PdfOptions::REGEXP_PRICE_LINES_01, PdfOptions::REGEXP_PRICE_LINES_02, PdfOptions::REGEXP_PRICE_LINES_03, PdfOptions::REGEXP_PRICE_LINES_04])
            ->reduce(function (Collection $matches, $regexp) use ($content) {
                preg_match_all($regexp, $content, $lines, PREG_UNMATCHED_AS_NULL, 0);
                return $matches->mergeRecursive(Arr::only($lines, $this->getColumnNames()));
            }, collect());

        if ($matches->isEmpty()) {
            return [];
        }

        /**
         * We are finding Service Agreement ID on each page and assign it to all rows on the page.
         */
        $count = count($matches->first());
        $said = $this->findSAID($content);

        $searchable = array_fill(0, $count, $said);
        $matches->put('searchable', $searchable);

        return $matches->toArray();
    }

    private function findPriceAttributes(string $content, array $attributes): array
    {
        preg_match(PdfOptions::REGEXP_PD, $content, $pd, PREG_UNMATCHED_AS_NULL);
        preg_match(PdfOptions::REGEXP_SH, $content, $sh, PREG_UNMATCHED_AS_NULL);
        preg_match(PdfOptions::REGEXP_SAID, $content, $said, PREG_UNMATCHED_AS_NULL);

        $foundAttributes = [
            'pricing_document'      => (array) Str::trim(last($pd)),
            'system_handle'         => (array) Str::trim(last($sh)),
            'service_agreement_id'  => (array) Str::trim(last($said))
        ];

        $attributes = array_merge_recursive(array_filter($attributes), $foundAttributes);

        $attributes = array_map(fn ($attribute) => array_values(array_flip(array_flip(array_filter($attribute)))), $attributes);

        return $attributes;
    }

    private function findSAID(string $content)
    {
        preg_match(PdfOptions::REGEXP_PRICE_SAID, $content, $matches, PREG_UNMATCHED_AS_NULL, 0);

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
            ->transform(fn ($item) => array_filter($item))
            ->reject(fn ($item) => empty($item));
    }

    private function mapPayments(Collection $payments, array $paymentOptions): array
    {
        return $payments->map(function ($payment, $key) use ($paymentOptions) {
            return [
                'from'  => data_get($paymentOptions, "0.{$key}"),
                'to'    => data_get($paymentOptions, "1.{$key}"),
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
        return collect($payments)
            ->filter(fn ($payment, $key) => data_get($colsMapping, $key))
            ->transform(fn ($payment) => Str::price($payment, false, true))
            ->values();
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
            ->filter(fn ($dates) => count($dates) === $colsCount);

        return $dates->merge($options)->toArray();
    }

    private function scheduleColsMapping(string $firstLine): array
    {
        preg_match_all(PdfOptions::REGEXP_SCHEDULE_COLUMNS, $firstLine, $matches, PREG_UNMATCHED_AS_NULL, 0);

        return collect($matches['cols'])->filter()
            ->transform(fn ($value) => (bool) preg_match(PdfOptions::REGEXP_SCHEDULE_DATE, $value))
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
        $periodFrom = $array[static::DT_FROM];
        $periodTo = $array[static::DT_TO];

        /** Find line-breaked periods. */
        foreach ($periodFrom as $key => $row) {
            $nextKey = $key + 1;

            if (!(!isset($periodFrom[$key]) &&
                !isset($periodFrom[$nextKey]) &&
                isset($periodTo[$key]) &&
                isset($periodTo[$nextKey]) &&
                $productNo[$key] === $productNo[$nextKey] &&
                $periodTo[$key] !== $periodTo[$nextKey])) {
                continue;
            }

            $periodFrom[$nextKey] = $periodTo[$nextKey];
            $periodTo[$nextKey] = null;
        }

        $resettedPeriods = [
            static::DT_FROM => $periodFrom,
            static::DT_TO => $periodTo
        ];

        return array_merge($array, $resettedPeriods);
    }

    private static function cacheExactDatesMatches(array $matches): void
    {
        $exactDates = [];

        $from = array_filter(Arr::get($matches, static::DT_FROM));
        $to = array_filter(Arr::get($matches, static::DT_TO));

        foreach ($from as $key => $value) {
            if (!isset($to[$key])) {
                continue;
            }

            $exactDates[$value] = static::DT_FROM;
            $exactDates[$to[$key]] = static::DT_TO;
        }

        static::$exactDatesMatchesCache = array_merge(static::$exactDatesMatchesCache, $exactDates);
    }

    private static function seekColumnsForOnePay(iterable $cells)
    {
        return Collection::wrap($cells)->contains(fn ($cell) => preg_match(PdfOptions::REGEXP_ONE_PAY, $cell));
    }

    private static function setExactDatesMatches(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            $from = Arr::get($row, static::DT_FROM);
            $to = Arr::get($row, static::DT_TO);

            if (filled($from) && filled($to)) {
                return $row;
            }

            if (Arr::get(static::$exactDatesMatchesCache, $from) === static::DT_TO) {
                Arr::set($row, static::DT_TO, $from);
                Arr::set($row, static::DT_FROM, null);
            }

            if (Arr::get(static::$exactDatesMatchesCache, $to) === static::DT_FROM) {
                Arr::set($row, static::DT_FROM, $to);
                Arr::set($row, static::DT_TO, null);
            }

            return $row;
        })->toArray();
    }
}
