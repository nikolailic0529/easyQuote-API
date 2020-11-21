<?php

namespace App\Services;

use App\Contracts\Repositories\VendorRepositoryInterface as Vendors;
use App\Contracts\Services\HpeExporter;
use App\DTO\HpeContractExportFile;
use App\DTO\PreviewHpeContractData;
use App\Models\QuoteTemplate\HpeContractTemplate;
use Barryvdh\Snappy\PdfWrapper;
use File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\{Arr, Str};
use Illuminate\Support\Collection;
use LynX39\LaraPdfMerger\PdfManage;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Contracts\Filesystem\Factory as DiskFactory;
use Spatie\PdfToText\Pdf as PdfToText;
use Smalot\PdfParser\Parser as PdfParser;

class HpeContractExporter implements HpeExporter
{
    protected Vendors $vendors;

    protected Disk $disk;

    protected string $exportView;

    public function __construct(Vendors $vendors, DiskFactory $diskFactory,  string $exportView = 'hpecontracts.pdf')
    {
        $this->vendors = $vendors;
        $this->disk = $diskFactory->disk();
        $this->exportView = $exportView;
    }

    public function export(HpeContractTemplate $template, PreviewHpeContractData $data)
    {
        $form = Collection::wrap($template->form_data);

        $form = static::sortFormPages($form);

        $data->images = Collection::wrap($this->retrieveTemplateImages($template, ThumbnailManager::PREFER_SVG))->pluck('abs_src', 'id')->toArray();

        $data->translations = $template->data_headers->pluck('value', 'key')->toArray();

        $tempDir = (new TemporaryDirectory)->create();

        $portraitPages = $tempDir->path('portrait-pages.pdf');
        
        $landscapePages = $tempDir->path('landscape-pages.pdf');

        $this->pdfWrapper()
            ->setPaper('letter', 'Portrait')
            ->setOption('margin-left', 26)
            ->setOption('margin-right', 26)
            ->loadView($this->exportView, ['form' => $form->only('first_page', 'contract_summary'), 'data' => $data, 'orientation' => 'P'])
            ->save($portraitPages, true);

        $this->pdfWrapper()
            ->setPaper('letter', 'Landscape')
            ->setOption('enable-javascript', true)
            ->loadView($this->exportView, ['form' => $form->except('first_page', 'contract_summary'), 'data' => $data, 'orientation' => 'L'])
            ->save($landscapePages, true);

        $pdfMerger = $this->pdfMerger()->init();

        $portraitPagesNumbers = $this->findFilledPages($portraitPages);
        $landscapePagesNumbers = $this->findFilledPages($landscapePages);

        $pdfMerger->addPDF($portraitPages, implode(',', $portraitPagesNumbers), 'P');
        $pdfMerger->addPDF($landscapePages, implode(',', $landscapePagesNumbers), 'L');
        $pdfMerger->merge();

        $filePath = 'hpe_contracts' . DIRECTORY_SEPARATOR .  static::makeDownloadFileName($data);

        $content = $pdfMerger->save(null, 'string');

        File::put($tempDir->path($filePath), $content);

        return new HpeContractExportFile([
            'filePath' => $tempDir->path($filePath),
            'fileName' => static::makeFileName($data)
        ]);
    }

    public function retrieveTemplateImages(HpeContractTemplate $template, int $flags = 0): array
    {
        $hpe = $this->vendors->findByCode('HPE');

        return Collection::wrap([$template->company, $hpe])
            ->whereInstanceOf(Model::class)
            ->reduce(function (Collection $carry, Model $model) use ($flags) {
                $images = ThumbnailManager::retrieveLogoDimensions(
                    $model->image,
                    $model->thumbnailProperties(),
                    get_class($model),
                    $flags
                );

                return $carry->merge($images);
            }, Collection::make())
            ->filter()
            ->toArray();
    }

    protected function pdfWrapper(): PdfWrapper
    {
        return app('snappy.pdf.wrapper');
    }

    protected function pdfMerger(): PdfManage
    {
        return app('pdf-merger');
    }

    protected function findFilledPages(string $filePath): array
    {
        /** @var PdfToText */
        $pdfToText = app(PdfToText::class);

        /** @var PdfParser */
        $pdfParser = app(PdfParser::class);

        $pagesCount = $pdfParser->parseFile($filePath)->getDetails()['Pages'];

        $allPagesNumbers = range(1, $pagesCount);

        $blankPages = [];

        foreach ($allPagesNumbers as $pageNumber) {
            $text = $pdfToText->setPdf($filePath)->setOptions(["f {$pageNumber}", "l {$pageNumber}"])->text();

            if (blank($text)) {
                $blankPages[] = $pageNumber;
            }
        }

        $filledPages = array_filter($allPagesNumbers, fn ($number) => !in_array($number, $blankPages));

        if (empty($filledPages)) {
            $filledPages = [1];
        }

        return $filledPages;
    }

    protected static function sortFormPages(Collection $form): Collection
    {
        $pagesOrder = [
            'first_page',
            'contract_summary',
            'contract_page',
            'contract_details',
            'support_service_details',
            'support_account_reference_detail',
            'asset_location_details',
            'serial_number_details',
        ];

        return $form->sortBy(fn ($page, $key) => Arr::get(array_flip($pagesOrder), $key));
    }

    protected static function makeFileName(PreviewHpeContractData $data): string
    {
        return sprintf('%s.pdf', (string) Str::of($data->purchase_order_no)->replace(['/', '\\'], ['_', '_'])->slug('_'));
    }

    protected static function makeDownloadFileName(PreviewHpeContractData $data): string
    {
        return sprintf('%s_%s.pdf', (string) Str::of($data->purchase_order_no)->replace(['/', '\\'], ['_', '_'])->slug('_'), Str::random(40));
    }
}
