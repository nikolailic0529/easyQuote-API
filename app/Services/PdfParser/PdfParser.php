<?php

namespace App\Services\PdfParser;

use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumns;
use App\Contracts\Services\PdfParserInterface;
use Illuminate\Support\{Arr, Facades\Storage, LazyCollection, Str,};
use Illuminate\Support\Collection;
use Smalot\PdfParser\Parser as SmalotPdfParser;
use Spatie\PdfToText\Pdf as SpatiePdfParser;

class PdfParser implements PdfParserInterface
{
    const CONTENT_OPTIONS = ['layout', 'eol unix', 'table', 'fixed 3.4'];
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
    )
    {
        $this->importableColumns = $importableColumns;
        $this->smalotPdfParser = $smalotPdfParser;
        $this->spatiePdfParser = $spatiePdfParser;
    }

    public function getText(string $filePath)
    {
        $count = $this->countPages($filePath);

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
                yield ['page' => $page['page'], 'content' => $page['content'] ?? Storage::get($page['file_path'])];
            }
        });

        $attributes = [];

        $pages = $pages->map(function ($page, $key) use (&$attributes) {
            ['page' => $page, 'content' => $content] = $page;

            $rows = [];

            $attributes = $this->findPriceAttributes($content, $attributes);

            [$matches, $previousPageLineAttributes] = $this->parseDistributorFilePage($content, $page);

            if (blank($matches)) {
                return ['page' => $page, 'rows' => [], 'previous_page_line_attributes' => []];
            }

            static::cacheExactDatesMatches($matches);

            /**
             * Resetting Coverage Periods for rows with the same Product Number
             */
            $matches = $this->resetCoveragePeriods($matches);

            $rows = $this->mapColumns($matches);

            return ['page' => $page, 'rows' => $rows, 'previous_page_line_attributes' => $previousPageLineAttributes];
        });

        $pages = $pages->all();

        $pages = $this->mapMovedPageLineAttributes($pages);

        $pages = collect($pages)
            ->map(function (array $page) {
                $rows = collect(static::setExactDatesMatches($page['rows']))
                    ->map(fn($row) => Arr::set($row, PdfOptions::SYSTEM_HEADER_ONE_PAY, static::seekColumnsForOnePay($row)))
                    ->toArray();

                return Arr::set($page, 'rows', $rows);
            })
            ->toArray();

        return compact('pages', 'attributes');
    }

    protected function mapMovedPageLineAttributes(array $pages): array
    {
        $mappedPages = [];

        foreach ($pages as $key => $page) {
            if (empty($page['previous_page_line_attributes']) || !isset($mappedPages[$key - 1]) || empty($mappedPages[$key - 1]['rows'])) {
                $mappedPages[] = $page;

                continue;
            }

            $lastRowKey = last(array_keys($mappedPages[$key - 1]['rows']));

            $mappedPages[$key - 1]['rows'][$lastRowKey] = array_map(function ($column) use (&$page) {
                if (!is_null($column)) {
                    return $column;
                }

                return array_shift($page['previous_page_line_attributes']);
            }, $mappedPages[$key - 1]['rows'][$lastRowKey]);

            $mappedPages[] = $page;
        }

        return $mappedPages;
    }

    public function parseSchedule(array $array)
    {
        $content = $array['content'];

        $paymentDateMatches = $this->findPaymentDates($content);
        $paymentPriceMatches = $this->findTotalPayments($content);
        $matches = $this->filterScheduleMatches($paymentDateMatches + ['payments' => $paymentPriceMatches]);

        if (!$matches->has(PdfOptions::SCHEDULE_MATCHES)) {
            return [];
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

    public function countPages(string $filePath)
    {
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

    private function parseDistributorFilePage(string $content, $page = null): array
    {
        /** @var \Illuminate\Support\Collection<\Illuminate\Support\Collection> */
        $matches = collect([
            PdfOptions::REGEXP_PRICE_LINES_01,
            PdfOptions::REGEXP_PRICE_LINES_02,
            PdfOptions::REGEXP_PRICE_LINES_03,
            PdfOptions::REGEXP_PRICE_LINES_04,
            PdfOptions::REGEXP_PRICE_LINES_05,
            PdfOptions::REGEXP_PRICE_LINES_06,
            PdfOptions::REGEXP_PRICE_LINES_SP,
        ])
            ->reduce(function (Collection $matches, $regexp) use ($content) {
                preg_match_all($regexp, $content, $lines, PREG_UNMATCHED_AS_NULL, 0);

                return $matches->mergeRecursive(Arr::only($lines, $this->getColumnNames()));
            }, collect());

        $dropPaymentMatches = [];

        foreach ($matches->get('description', []) as $key => $desc) {
            if (preg_match('/(\d{2}\.\d{2}\.\d{4})|(^\h*\d+\.?\d*\h*$)/', $desc)) {
                array_push($dropPaymentMatches, $key);
            }
        }

        $matches->transform(function ($column) use ($dropPaymentMatches) {
            foreach ($dropPaymentMatches as $key) {
                unset($column[$key]);
            }

            return $column;
        });

        // There can be a case when a serial number is moved to the page from the previous page.
        // We need to match the table header, and ensure if the first line does not have a product number.
        $previousPageLineAttributes = with($content, function ($content) {
            preg_match_all('/Product.+Description.+Serial.+Coverage.+Qty.+Price.+\s+from.+to.+\n+\h{20,}(.+)/im', $content, $matches);

            if (empty($matches[1])) {
                return [];
            }

            return preg_split('/\h{5,}/', $matches[1][0]);
        });

        /**
         * Sometimes PDF pages contain unexpected gaps inside the line decription column.
         * We'll assume to handle this case using the parts of each line: (product number + description) & (serial number + from date + to date + quantity + price)
         */
        if ($matches->filter()->isEmpty()) {
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $content) as $line) {
                if (preg_match(PdfOptions::REGEXP_PRICE_LINES_GAPS, $line, $lineMatches)) {
                    $parts = Arr::only($lineMatches, array_merge($this->getColumnNames(), ['left_part']));

                    $leftPart = Str::of($parts['left_part'] ?? '');

                    $productNo = $leftPart->before(' ')->trim();

                    if ($productNo->isEmpty()) {
                        continue;
                    }

                    $description = $leftPart->after((string)$productNo)->trim()->replaceMatches('/\h{1,}/', ' ');

                    $otherParts = Arr::except($parts, 'left_part');

                    $parts = array_merge([
                        'product_no' => (string)$productNo,
                        'description' => (string)$description,
                    ], $otherParts);

                    $matches = $matches->mergeRecursive($parts);
                }
            }
        }

        /**
         * We'll assume if we haven't found any rows on the page, the page may contain lines without serial number.
         */
        if ($matches->filter()->isEmpty()) {
            $matches = collect(PdfOptions::REGEXP_PRICE_LINES_NS)
                ->reduce(function (Collection $matches, $regexp) use ($content, $page) {
                    preg_match_all($regexp, $content, $lines, PREG_UNMATCHED_AS_NULL, 0);
                    return $matches->mergeRecursive(Arr::only($lines, $this->getColumnNames()));
                }, collect());
        }

        if ($matches->isEmpty()) {
            return [[], []];
        }

        /**
         * We are finding Service Agreement ID on each page and assign it to all rows on the page.
         */
        $count = count($matches->first());
        $said = $this->findSAID($content);

        $searchable = array_fill(0, $count, $said);
        $matches->put('searchable', $searchable);

        return [$matches->toArray(), $previousPageLineAttributes];
    }

    private function findPriceAttributes(string $content, array $attributes): array
    {
        preg_match(PdfOptions::REGEXP_PD, $content, $pd, PREG_UNMATCHED_AS_NULL);
        preg_match(PdfOptions::REGEXP_SH, $content, $sh, PREG_UNMATCHED_AS_NULL);
        preg_match(PdfOptions::REGEXP_SAID, $content, $said, PREG_UNMATCHED_AS_NULL);

        $foundAttributes = [
            'pricing_document' => (array)Str::trim(last($pd)),
            'system_handle' => (array)Str::trim(last($sh)),
            'service_agreement_id' => (array)Str::trim(last($said)),
        ];

        $attributes = array_merge_recursive(array_filter($attributes), $foundAttributes);

        $attributes = array_map(fn($attribute) => array_values(array_flip(array_flip(array_filter($attribute)))), $attributes);

        return $attributes;
    }

    private function findSAID(string $content)
    {
        preg_match(PdfOptions::REGEXP_PRICE_SAID, $content, $matches, PREG_UNMATCHED_AS_NULL, 0);

        return optional($matches)[1];
    }

    private function findPaymentDates(string $content): array
    {
        preg_match_all(PdfOptions::REGEXP_SCHEDULE_PAYMENTS, $content, $matches, PREG_UNMATCHED_AS_NULL);

        return $matches;
    }

    private function findTotalPayments(string $content): array
    {
        $payments = [];

        if (preg_match_all('/(Total|Celkem)\s*/i', $content, $totals, PREG_SET_ORDER)) {
            $totals = Str::afterLast($content, head(last($totals)));

            preg_match_all(PdfOptions::REGEXP_SCHEDULE_PRICE, $totals, $payments, PREG_UNMATCHED_AS_NULL, 0);
        };

        if (empty($payments)) {
            $contentLines = explode("\n", $content);

            foreach ($contentLines as $line) {
                preg_match_all(PdfOptions::REGEXP_SCHEDULE_PRICE, $line, $payments, PREG_UNMATCHED_AS_NULL, 0);

                // Continue prices lookup if the payment dates are found as prices.
                $priceMatches = array_filter($payments['price'] ?? [], fn(string $priceMatch) => substr_count($priceMatch, '.') < 2);

                if (!empty($priceMatches)) {
                    $payments['price'] = $priceMatches;

                    break;
                }
            }
        }

        return data_get($payments, 'price', []);
    }

    private function filterScheduleMatches(array $matches): Collection
    {
        return collect($matches)->only(PdfOptions::SCHEDULE_MATCHES)
            ->transform(fn($item) => array_filter($item))
            ->reject(fn($item) => empty($item));
    }

    private function mapPayments(Collection $payments, array $paymentOptions): array
    {
        return $payments->map(function ($payment, $key) use ($paymentOptions) {
            return [
                'from' => data_get($paymentOptions, "0.{$key}"),
                'to' => data_get($paymentOptions, "1.{$key}"),
                'price' => $payment,
            ];
        })->toArray();
    }

    private function mapColumns(array $columns): array
    {
        $rows = [];

        // Count the lines by product_no column as it must be always present.
        $linesCount = count(Arr::get($columns, 'product_no') ?? []);

        for ($i = 0; $i < $linesCount; $i++) {
            $row = [];

            foreach (PdfOptions::PRICE_COLS as $column) {
                $row[$column] = $this->handleColumnValue(Arr::get($columns, "$column.$i"));
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function filterSchedulePayments(array $payments, array $colsMapping): Collection
    {
        return collect($payments)
            ->filter(fn($payment, $key) => data_get($colsMapping, $key))
            ->transform(fn($payment) => Str::price($payment, false, true))
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
            ->filter(fn($dates) => count($dates) === $colsCount);

        return $dates->merge($options)->toArray();
    }

    private function scheduleColsMapping(string $firstLine): array
    {
        $firstLine = Str::ascii($firstLine);

        preg_match_all(PdfOptions::REGEXP_SCHEDULE_COLUMNS, $firstLine, $matches, PREG_UNMATCHED_AS_NULL, 0);

        return array_values(
            array_map(function ($col) {
                return (bool)preg_match(PdfOptions::REGEXP_SCHEDULE_DATE, $col);
            }, array_filter($matches['cols']))
        );
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
            static::DT_TO => $periodTo,
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
        return Collection::wrap($cells)->contains(fn($cell) => preg_match(PdfOptions::REGEXP_ONE_PAY, $cell));
    }

    private static function setExactDatesMatches(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            $from = Arr::get($row, static::DT_FROM);
            $to = Arr::get($row, static::DT_TO);

            if (filled($from) && filled($to)) {
                return $row;
            }

            $exactlyMatches = false;

            if (Arr::get(static::$exactDatesMatchesCache, $from) === static::DT_TO) {
                Arr::set($row, static::DT_TO, $from);
                Arr::set($row, static::DT_FROM, null);

                $exactlyMatches = true;
            }

            if (Arr::get(static::$exactDatesMatchesCache, $to) === static::DT_FROM) {
                Arr::set($row, static::DT_FROM, $to);
                Arr::set($row, static::DT_TO, null);

                $exactlyMatches = true;
            }

            if (false === $exactlyMatches && blank($from) && filled($to)) {
                Arr::set($row, static::DT_TO, null);
                Arr::set($row, static::DT_FROM, $to);
            }

            return $row;
        })->toArray();
    }
}
