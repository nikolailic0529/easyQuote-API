<?php

namespace App\Domain\HpeContract\Services;

use App\Domain\HpeContract\Contracts\HpeExporter;
use App\Domain\HpeContract\DataTransferObjects\HpeContractExportFile;
use App\Domain\HpeContract\DataTransferObjects\PreviewHpeContractData;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Vendor\Contracts\VendorRepositoryInterface as Vendors;
use App\Foundation\Filesystem\TemporaryDirectory;
use Barryvdh\Snappy\PdfWrapper;
use iio\libmergepdf\Merger;
use iio\libmergepdf\Pages;
use Illuminate\Contracts\Filesystem\Factory as DiskFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use Spatie\PdfToText\Pdf as PdfToText;

class HpeContractExporter implements HpeExporter
{
    protected Vendors $vendors;

    protected Disk $disk;

    public function __construct(Vendors $vendors, DiskFactory $diskFactory)
    {
        $this->vendors = $vendors;
        $this->disk = $diskFactory->disk();
    }

    public function export(HpeContractTemplate $template, PreviewHpeContractData $data)
    {
        $form = Collection::wrap($template->form_data);
        $form = static::sortFormPages($form);

        $exportView = $this->resolveExportView($template);

        $templateImages = $this->retrieveTemplateImages($template, ThumbHelper::PREFER_SVG | ThumbHelper::ABS_PATH);

        $data->images = Collection::wrap($templateImages)->pluck('src', 'id')->all();

        $data->translations = $template->data_headers->pluck('value', 'key')->toArray();

        $tempDir = (new TemporaryDirectory())->create();

        $portraitPages = $tempDir->path('portrait-pages.pdf');

        $landscapePages = $tempDir->path('landscape-pages.pdf');

        $this->pdfWrapper()
            ->setPaper('letter', 'Portrait')
            ->setOption('margin-left', 26)
            ->setOption('margin-right', 26)
            ->loadView($exportView, ['form' => $form->only('first_page', 'contract_summary'), 'data' => $data, 'orientation' => 'P'])
            ->save($portraitPages, true);

        $this->pdfWrapper()
            ->setPaper('letter', 'Landscape')
            ->setOption('enable-javascript', true)
            ->loadView($exportView, ['form' => $form->except('first_page', 'contract_summary'), 'data' => $data, 'orientation' => 'L'])
            ->save($landscapePages, true);

        $merger = new Merger();

        $portraitPagesNumbers = $this->findFilledPages($portraitPages);
        $landscapePagesNumbers = $this->findFilledPages($landscapePages);

        $merger->addFile($portraitPages, new Pages(implode(',', $portraitPagesNumbers)));
        $merger->addFile($landscapePages, new Pages(implode(',', $landscapePagesNumbers)));

        $content = $merger->merge();

        $filePath = 'hpe_contracts'.DIRECTORY_SEPARATOR.static::makeDownloadFileName($data);

        File::put($tempDir->path($filePath), $content);

        return new HpeContractExportFile([
            'filePath' => $tempDir->path($filePath),
            'fileName' => static::makeFileName($data),
        ]);
    }

    public function retrieveTemplateImages(HpeContractTemplate $template, int $flags = 0): array
    {
        $vendor = $template->vendor;

        return Collection::wrap([$template->company, $vendor])
            ->whereInstanceOf(Model::class)
            ->reduce(function (Collection $carry, Model $model) use ($flags) {
                $images = ThumbHelper::getLogoDimensionsFromImage(
                    $model->image,
                    $model->thumbnailProperties(),
                    Str::snake(class_basename($model::class)),
                    $flags
                );

                return $carry->merge($images);
            }, Collection::make())
            ->filter()
            ->toArray();
    }

    protected function resolveExportView(HpeContractTemplate $template): string
    {
        return [
            'HPE' => 'hpecontracts.hpe-pdf',
            'ARU' => 'hpecontracts.aruba-pdf',
        ][$template->vendor->short_code] ?? 'hpecontracts.aruba-pdf';
    }

    protected function pdfWrapper(): PdfWrapper
    {
        return app('snappy.pdf.wrapper');
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
