<?php

namespace App\Services;

use App\Contracts\Repositories\VendorRepositoryInterface as Vendors;
use App\Contracts\Services\HpeExporter;
use App\DTO\PreviewHpeContractData;
use App\Models\QuoteTemplate\HpeContractTemplate;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LynX39\LaraPdfMerger\PdfManage;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Contracts\Filesystem\Factory as DiskFactory;

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

    public function export(HpeContractTemplate $template, PreviewHpeContractData $data, bool $web = false)
    {
        // $form = Collection::wrap(json_decode(file_get_contents(database_path('seeds/models/hpe_contract_template_design.json')), true));
        $form = Collection::wrap($template->form_data);

        $form = static::sortFormPages($form);

        $data->images = Collection::wrap($this->retrieveTemplateImages($template, true))->pluck('abs_src', 'id')->toArray();

        $data->translations = $template->data_headers->pluck('value', 'key')->toArray();

        if ($web) {
            return view($this->exportView, ['data' => $data, 'form' => $form, 'web' => true]);
        }

        $tempDir = (new TemporaryDirectory)->create();

        $portraitPages = $tempDir->path('portrait-pages.pdf');
        $landscapePages = $tempDir->path('landscape-pages.pdf');

        $this->pdfWrapper()
            ->setPaper('letter', 'Portrait')
            ->setOption('margin-left', 26)
            ->setOption('margin-right', 26)
            ->loadView($this->exportView, ['form' => $form->only('first_page', 'contract_summary'), 'data' => $data])
            ->save($portraitPages, true);

        $this->pdfWrapper()
            ->setPaper('letter', 'Landscape')
            ->loadView($this->exportView, ['form' => $form->except('first_page', 'contract_summary'), 'data' => $data])
            ->save($landscapePages, true);

        $pdfMerger = $this->pdfMerger()->init();

        $pdfMerger->addPDF($portraitPages, 'all', 'P');
        $pdfMerger->addPDF($landscapePages, 'all', 'L');
        $pdfMerger->merge();

        $fileName = $this->disk->path('hpe_contracts' . DIRECTORY_SEPARATOR .  static::makeFileName($data));

        return tap($fileName, fn () => $pdfMerger->save(static::makeFileName($data), 'download'));
    }

    public function retrieveTemplateImages(HpeContractTemplate $template, bool $preferSvg = false): array
    {
        $hpe = $this->vendors->findByCode('HPE');

        return Collection::wrap([$template->company, $hpe])
            ->whereInstanceOf(Model::class)
            ->reduce(function (Collection $carry, Model $model) use ($preferSvg) {
                $images = ThumbnailManager::retrieveLogoDimensions(
                    $model->image,
                    $model->thumbnailProperties(),
                    get_class($model),
                    false,
                    false,
                    $preferSvg
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
        return sprintf('%s.pdf', $data->contract_number);
    }
}
