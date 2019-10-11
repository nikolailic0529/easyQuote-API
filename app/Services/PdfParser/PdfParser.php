<?php namespace App\Services\PdfParser;

use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface as ImportableColumnRepository;
use Smalot\PdfParser\Parser as SmalotPdfParser;
use Spatie\PdfToText\Pdf as SpatiePdfParser;
use Illuminate\Support\LazyCollection;
use Storage;

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

    public function getText(string $path)
    {
        $filePath = Storage::path($path);

        $count = $this->countPages($path);

        $rawPages = collect()->times($count, function ($page) use ($filePath) {
            $content = $this->getPageContent($filePath, $page);
            return compact('page', 'content');
        });

        return $rawPages->toArray();
    }

    public function getPageContent(string $path, int $page)
    {
        return $this->spatiePdfParser->setPdf($path)
            ->setOptions(['layout', 'table', "f {$page}", "l {$page}"])
            ->text();
    }

    public function parse(array $array)
    {
        $regexpColumns = $this->importableColumn->allColumnsRegs();

        $regexp = '/^(?<product_no>\d+\-\w{3}|[a-zA-Z]\w{3,4}?[a-zA-Z]{1,2})\s+(?<description>.+?\w)\s+(?<serial_no>[a-zA-Z]{2,3}[a-zA-Z\d]{7,8})\s+((?<date_from>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?((?<date_to>(([0-2][0-9])|(3[0-1]))[\.\/]((0[0-9])|(1[0-2]))[\.\/]\d{4})\s+?)?(?<qty>\d{1,3}(?=\s+?))?(\s+?((\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,2}))))([a-zA-Z].+?)?/m';

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
        $lastPage = collect($array)->last();
        $filePath = $lastPage['file_path'];

        $content = Storage::get($filePath);

        $priceGroup = '(\s+?((\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,2}))))';
        $dateGroup = '(?<date>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{4})';

        $regexp = '/(?<payment>^(?<account>[ ]?\w[\w -]+)(\s+?((\p{Sc})?\s?(?<price>(\d{1,3},)?\d+([,\.]\d{1,2}))))+(\r\n|\n))|(?<payment_dates>(?:system handle)(?:(?:[^\S]+)?(?<date>(?:(?:[0-2][0-9])|(?:3[0-1]))[\.\/](?:(?:0[0-9])|(?:1[0-2]))[\.\/]\d{4}))+?(\r\n|\n))|((\r\n|\n)?^(?:(?<payment_dates_options>((?:[\s]+)?\g\'date\')+)))/mi';

        preg_match_all($regexp, $content, $matches, PREG_UNMATCHED_AS_NULL, 0);

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

        $array = [
            'from' => $paymentOptions[0][0],
            'to' => $paymentOptions[1][0]
        ];

        foreach ($paymentLines[0] as $key => $price) {
            $from = $paymentOptions[0][$key] ?? null;
            $to = $paymentOptions[1][$key] ?? null;
            $paymentSchedule[$key] = compact('from', 'to', 'price');
        };

        return $paymentSchedule;
    }

    public function countPages(string $path)
    {
        $filePath = Storage::path($path);

        $document = $this->smalotPdfParser->parseFile($filePath);

        return count($document->getPages());
    }

    private function handleValues(array $array)
    {
        $description = collect($array['description']);

        $description->transform(function ($value) {
            if(is_null($value)) {
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
        if(config('app.env') === 'local') {
            return $this->binPath = app_path(config('pdfparser.pdftotext.win'));
        }

        if(config('app.env') === 'production') {
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

            if(!(
                isset($periodFrom[$key]) &&
                isset($periodFrom[$nextKey]) &&
                !isset($periodTo[$key]) &&
                !isset($periodTo[$nextKey]) &&
                $productNo[$key] === $productNo[$nextKey]
            )) {
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
