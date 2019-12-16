<?php

namespace App\Services\PdfParser;

use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumnRepository;
use Smalot\PdfParser\Parser as SmalotPdfParser;
use Spatie\PdfToText\Pdf as SpatiePdfParser;
use Illuminate\Support\LazyCollection;
use Storage, Str;

class PdfParser implements PdfParserInterface
{
    protected $importableColumn;

    protected $smalotPdfParser;

    protected $spatiePdfParser;

    protected $binPath;

    public function __construct(
        ImportableColumnRepository $importableColumn,
        SmalotPdfParser $smalotPdfParser
    ) {
        $this->importableColumn = $importableColumn;
        $this->smalotPdfParser = $smalotPdfParser;
        $this->setBinPath();
        $this->spatiePdfParser = new SpatiePdfParser($this->binPath);
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
        $regexp = '/^(?<product_no>\d+\-\w{3}|[a-zA-Z]\w{3,4}?[a-zA-Z]{1,2})\s+(?<description>.+?\w)\s+(?<serial_no>\d?[a-zA-Z]{1,3}[a-zA-Z\d]{7,8})\s+((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))?(\s+?((\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,2}))))([a-zA-Z].+?)?$/m';

        $pages = LazyCollection::make(function () use ($array) {
            foreach ($array as $page) {
                yield ['page' => $page['page'], 'content' => Storage::get($page['file_path'])];
            }
        });

        $pagesData = $pages->map(function ($page, $key) use ($regexp) {
            ['page' => $page, 'content' => $content] = $page;

            preg_match_all($regexp, $content, $matches, PREG_UNMATCHED_AS_NULL, 0);

            $columnsAliases = $this->importableColumn->allNames();

            $matches = collect($matches)->only($columnsAliases)->toArray();

            $matches = $this->handleValues($matches);

            /**
             * Resetting Coverage Periods for rows with the same Product Number
             */
            $matches = $this->resetCoveragePeriods($matches);

            $rows = [];

            foreach ($matches as $column => $values) {
                foreach ($values as $key => $value) {
                    $rows[$key][$column] = $value ? trim($value) : null;
                }
            }

            return compact('page', 'rows');
        });

        return $pagesData->toArray();
    }

    public function parseSchedule(array $array)
    {
        $filePath = $array['file_path'];

        $content = Storage::get($filePath);

        // dd($content);

        $priceGroup = '(\h{2,}((\p{Sc})?[ ]?(?<price>([\d]+[ ]?,?)?[,\.]?\d+[,\.]?\d+)))';
        $dateGroup = '(?<date>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{2,4})';

        $regexp = '/(?<payment_dates>(?:system handle|periode de|from)(?<date>(?:[\ha-z-]+)(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))([\.\/]\d{2,4})(?:[\ha-z-]*?))+$)|(^(\h)?(?!payment (\h+)? schedule)(?:period au|to)?(?<payment_dates_options>(\g\'date\')+(?:([\ha-z-]+)?)$))|(?<payment>^(?<account>\h?\w[\w\h-]+?)(\h{2,}((\p{Sc})?[ ]?(?<price>([\d]+[ ]?,?)?[,\.]?\d+[,\.]?\d+)))+$)/mi';

        preg_match_all($regexp, $content, $matches, PREG_UNMATCHED_AS_NULL, 0);

        $matches = collect($matches)->only('payment', 'account', 'payment_dates', 'payment_dates_options')->map(function ($item) {
            return collect($item)->filter(function ($value) {
                return !is_null($value);
            })->map(function ($value) {
                return trim($value);
            })->values();
        });

        if ($matches['account']->isEmpty() || $matches['payment']->isEmpty() || ($matches['payment_dates']->isEmpty() && $matches['payment_dates_options']->isEmpty())) {
            throw new \ErrorException(QFNS_01);
        }

        $account = str_replace(' ', '', data_get($matches, 'account.0'));
        $paymentLines = data_get($matches, 'payment');
        $paymentDatesStandard = data_get($matches, 'payment_dates.0');
        $paymentDatesOptions = data_get($matches, 'payment_dates_options', []);

        // Payment dates options
        $datesRegexp = "/{$dateGroup}/";
        $paymentOptions = [];

        preg_match_all($datesRegexp, $paymentDatesStandard, $matches);
        $paymentOptions[] = $matches['date'];

        $wholeRegexp = '/(?:periode de|system handle|from\h+)|(?<cols>(?<date>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{2,4})|(\b(\w+)\b))/mi';
        preg_match_all($wholeRegexp, $paymentDatesStandard, $matches, PREG_UNMATCHED_AS_NULL, 0);

        $colsMapping = collect($matches['cols'])->filter(function ($value) {
            return isset($value);
        })->values()->transform(function ($value) use ($datesRegexp) {
            return (bool) preg_match($datesRegexp, $value);
        });

        $colsCount = $colsMapping->filter()->count();

        foreach ($paymentDatesOptions as $paymentOption) {
            preg_match_all($datesRegexp, $paymentOption, $matches);

            if (count(data_get($matches, 'date', [])) !== $colsCount) {
                continue;
            }

            $paymentOptions[] = $matches['date'];
        }

        // Payment Lines
        $paymentsRegexp = "/{$priceGroup}/";

        $paymentLines = collect($paymentLines)
            ->transform(function ($paymentLine) use ($paymentsRegexp) {
                preg_match_all($paymentsRegexp, $paymentLine, $matches);
                return data_get($matches, 'price');
            })->transform(function ($line) use ($colsMapping) {
                return collect($line)->filter(function ($payment, $key) use ($colsMapping) {
                    return data_get($colsMapping, $key);
                })->transform(function ($payment) {
                    return Str::price($payment, false, true);
                });
            });

        /**
         * Removal of unnecessary Prices
         */
        $paymentLines->transform(function ($line) use ($paymentOptions) {
            return collect($line)->splice(0, count(head($paymentOptions)));
        });

        $paymentSchedule = [];

        $array = [
            'from' => data_get($paymentOptions, '0.0'),
            'to' => data_get($paymentOptions, '1.0')
        ];

        foreach ($paymentLines[0] as $key => $price) {
            $from = data_get($paymentOptions, "0.{$key}");
            $to = data_get($paymentOptions, "1.{$key}");
            data_set($paymentSchedule, $key, compact('from', 'to', 'price'));
        };

        return $paymentSchedule;
    }

    public function countPages(string $path, bool $storage = true)
    {
        $filePath = $storage ? Storage::path($path) : $path;

        $document = $this->smalotPdfParser->parseFile($filePath);

        return count($document->getPages());
    }

    private function handleValues(array $array)
    {
        $description = collect($array['description']);

        $description->transform(function ($value) {
            if (is_null($value)) {
                return $value;
            }

            /**
             * Prevent multi-spaces
             */
            $value = preg_replace('/\s{2,}/', ' ', $value);

            return $value;
        })->toArray();

        return array_merge($array, compact('description'));
    }

    private function setBinPath()
    {
        if (in_array(config('app.env'), ['local', 'testing'])) {
            return $this->binPath = app_path(config('pdfparser.pdftotext.win'));
        }

        if (config('app.env') === 'production' && !config('pdfparser.pdftotext.default_bin')) {
            return $this->binPath = config('pdfparser.pdftotext.linux');
        }
    }

    private function resetCoveragePeriods(array $array)
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
