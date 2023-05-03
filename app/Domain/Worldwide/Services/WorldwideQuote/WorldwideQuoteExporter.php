<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote;

use App\Domain\Template\DataTransferObjects\TemplateElement;
use App\Domain\Template\DataTransferObjects\TemplateElementChildControl;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\TemplateData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\WorldwideDistributionData;
use App\Domain\Worldwide\DataTransferObjects\Quote\Export\WorldwideQuotePreviewData;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteExported;
use App\Domain\Worldwide\Events\SalesOrder\SalesOrderExported;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Services\WorldwideQuote\Models\PageLocatedElements;
use App\Domain\Worldwide\Services\WorldwideQuote\Models\QuoteExportResult;
use App\Foundation\Filesystem\TemporaryDirectory;
use App\Foundation\Validation\Exceptions\ValidationException;
use Barryvdh\Snappy\PdfWrapper;
use iio\libmergepdf\Merger;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Spatie\PdfToText\Pdf as PdfToText;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WorldwideQuoteExporter
{
    public function __construct(
        protected PdfWrapper $pdfWrapper,
        protected ViewFactory $viewFactory,
        protected ValidatorInterface $validator,
        protected Dispatcher $eventDispatcher,
        protected PdfToText $pdfToText
    ) {
    }

    public function export(
        WorldwideQuotePreviewData $previewData,
        WorldwideQuote|SalesOrder $exportedEntity
    ): QuoteExportResult {
        $this->mapTemplateDataControls($previewData);

        $rfqNumber = $previewData->quote_summary->export_file_name;

        $event = match ($exportedEntity::class) {
            WorldwideQuote::class => new WorldwideQuoteExported($exportedEntity),
            SalesOrder::class => new SalesOrderExported($exportedEntity),
        };

        $this->eventDispatcher->dispatch($event);

        $tempDir = (new TemporaryDirectory())->create();

        $pagePaths = [];

        foreach ($this->iterateTemplateDataPages($previewData->template_data) as $name => $pageElements) {
            /** @var TemplateElement[] $pageElements */
            $locatedElements = $this->locatePageTemplateElements(...$pageElements);

            $pagePaths[] = $pagePath = $tempDir->path(Str::random().'.pdf');

            $this->pdfWrapper
                ->loadView(
                    view: 'ww-quotes.pdf-page',
                    data: ['elements' => $locatedElements->bodyElements]
                )
                ->setPaper('letter', 'Portrait')
                ->setOption('margin-bottom', '15')
                ->setOption('footer-html', $this->viewFactory->make('ww-quotes.pdf-footer', ['elements' => $locatedElements->footerElements]))
                ->save($pagePath, true);
        }

        $merger = new Merger();

        foreach ($pagePaths as $path) {
            if (false === $this->isPdfBlank($path)) {
                $merger->addFile($path);
            }
        }

        return new QuoteExportResult(
            content: $merger->merge(),
            filename: "$rfqNumber.pdf"
        );
    }

    private function isPdfBlank(string $filepath): bool
    {
        return blank($this->pdfToText->setPdf($filepath)->text());
    }

    private function iterateTemplateDataPages(TemplateData $templateData): \Generator
    {
        foreach ($templateData as $page => $elements) {
            if (in_array($page, ['template_assets', 'headers'])) {
                continue;
            }

            yield $page => $elements;
        }
    }

    private function locatePageTemplateElements(TemplateElement ...$elements): PageLocatedElements
    {
        $bodyElements = [];
        $footerElements = [];

        foreach ($elements as $element) {
            if (str_contains($element->css, 'footer-html')) {
                if (!in_array($element, $footerElements, true)) {
                    $footerElements[] = $element;
                }
            } else {
                $bodyElements[] = $element;
            }
        }

        return new PageLocatedElements(bodyElements: $bodyElements, footerElements: $footerElements);
    }

    public function buildView(WorldwideQuotePreviewData $previewData): View
    {
        $this->mapTemplateDataControls($previewData);

        return $this->viewFactory->make(
            'ww-quotes.web-preview',
            ['template_data' => $previewData->template_data]
        );
    }

    private function mapTemplateDataControls(WorldwideQuotePreviewData $previewData): void
    {
        $violations = $this->validator->validate($previewData);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        match ($previewData->contract_type_name) {
            'Pack' => $this->mapPackTemplateDataControls($previewData),
            'Contract' => $this->mapContractTemplateDataControls($previewData),
        };
    }

    private function mapContractTemplateDataControls(WorldwideQuotePreviewData $previewData): void
    {
        $this->hideTemplateElements($previewData);

        $templateData = $previewData->template_data;

        // First page schema
        foreach ($this->getTemplateControlsIterator($templateData->first_page_schema) as $control) {
            $this->mapContractTemplateControl($control, $previewData);
        }

        $assetsPageSchema = [];

        foreach ($previewData->distributions as $distribution) {
            $distributionAssetsSchema = $templateData->assets_page_schema;

            foreach ($this->getTemplateControlsIterator($distributionAssetsSchema) as $control) {
                $this->mapContractTemplateControlForDistributorQuote($control, $distribution, $previewData);
            }

            $distributionAssetsSchema[] = new TemplateElement([
                'children' => [],
                'class' => 'page-break',
                'css' => '',
            ]);

            $assetsPageSchema = array_merge($assetsPageSchema, $distributionAssetsSchema);
        }

        $templateData->assets_page_schema = $assetsPageSchema;

        $paymentsPageSchema = [];

        foreach ($previewData->distributions as $distribution) {
            if (false === $distribution->has_payment_schedule_data) {
                continue;
            }

            $distributionPaymentsSchema = $templateData->payment_schedule_page_schema;

            $paymentsElement = \with(true, function () use ($distribution) {
                $assetsView = $this->viewFactory->make(
                    'ww-quotes.components.distribution_payments',
                    [
                        'payment_schedule_fields' => $distribution->payment_schedule_fields,
                        'payment_schedule_data' => $distribution->payment_schedule_data,
                    ]
                );

                $controls = \with([], function (array $controls) use ($assetsView, $distribution) {
                    $controls[] = [
                        'id' => (string) Str::uuid(),
                        'type' => 'h',
                        'class' => '',
                        'value' => "$distribution->vendors > $distribution->country",
                    ];

                    $controls[] = [
                        'id' => 'distribution_payments_data',
                        'type' => 'tag',
                        'class' => '',
                        'css' => '',
                        'value' => $assetsView->render(),
                    ];

                    return $controls;
                });

                return new TemplateElement([
                    'children' => [
                        [
                            'id' => (string) Str::uuid(),
                            'class' => 'col-lg-12',
                            'controls' => $controls,
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'class' => 'page-break',
                            'controls' => [],
                        ],
                    ],
                    'class' => '',
                    'css' => '',
                ]);
            });

            $distributionPaymentsSchema[] = $paymentsElement;

            $paymentsPageSchema = array_merge($paymentsPageSchema, $distributionPaymentsSchema);
        }

        $templateData->payment_schedule_page_schema = $paymentsPageSchema;

        // Last page schema
        foreach ($this->getTemplateControlsIterator($templateData->last_page_schema) as $control) {
            $this->mapContractTemplateControl($control, $previewData);
        }
    }

    private function mapContractTemplateControl(
        TemplateElementChildControl $control,
        WorldwideQuotePreviewData $previewData
    ): void {
        $templateData = $previewData->template_data;

        if ('img' === $control->type) {
            if (isset($templateData->template_assets->{$control->id})) {
                $control->value = $templateData->template_assets->{$control->id} ?? '';
            }

            return;
        }

        if ('tag' === $control->type) {
            $value = $previewData->quote_summary->{$control->id} ?? '';

            if (str_starts_with($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                    'class' => $control->class,
                    'images' => $templateData->template_assets->{$control->id},
                ])->render();

                return;
            }

            if ('quote_data_aggregation' === $control->id) {
                $control->value = $this->viewFactory->make('ww-quotes.components.quote_data_aggregation', [
                    'aggregation_data' => $previewData->quote_summary->quote_data_aggregation,
                    'aggregation_fields' => $previewData->quote_summary->quote_data_aggregation_fields,
                    'sub_total_value' => $previewData->quote_summary->sub_total_value,
                    'total_value_including_tax' => $previewData->quote_summary->total_value_including_tax,
                    'grand_total_value' => $previewData->quote_summary->grand_total_value,
                    'headers' => $previewData->template_data->headers,
                ])->render();

                return;
            }

            if (is_scalar($value)) {
                $control->value = $value;

                return;
            }

            return;
        }
    }

    private function mapContractTemplateControlForDistributorQuote(
        TemplateElementChildControl $control,
        WorldwideDistributionData $distributorQuoteData,
        WorldwideQuotePreviewData $previewData
    ): void
    {
        $templateData = $previewData->template_data;

        if ('img' === $control->type) {
            if (isset($templateData->template_assets->{$control->id})) {
                $control->value = $templateData->template_assets->{$control->id} ?? '';
            }

            return;
        }

        if ('tag' === $control->type) {
            $value = $distributorQuoteData->{$control->id} ?? $previewData->quote_summary->{$control->id} ?? '';

            if (str_starts_with($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                    'class' => $control->class,
                    'images' => $templateData->template_assets->{$control->id},
                ])->render();

                return;
            }

            if ('quote_data_aggregation' === $control->id) {
                $control->value = $this->viewFactory->make('ww-quotes.components.quote_data_aggregation', [
                    'aggregation_data' => $previewData->quote_summary->quote_data_aggregation,
                    'aggregation_fields' => $previewData->quote_summary->quote_data_aggregation_fields,
                    'sub_total_value' => $previewData->quote_summary->sub_total_value,
                    'total_value_including_tax' => $previewData->quote_summary->total_value_including_tax,
                    'grand_total_value' => $previewData->quote_summary->grand_total_value,
                    'headers' => $previewData->template_data->headers,
                ])->render();

                return;
            }

            if (is_scalar($value)) {
                $control->value = $value;

                return;
            }

            return;
        }

        if ('quote_assets' === $control->type) {
            $assetsViewName = $distributorQuoteData->assets_are_grouped
                ? 'ww-quotes.components.distribution_grouped_assets'
                : 'ww-quotes.components.distribution_assets';

            $control->value = $this->viewFactory->make(
                $assetsViewName, [
                    'asset_fields' => $distributorQuoteData->asset_fields,
                    'assets_data' => $distributorQuoteData->assets_data,
                    'asset_notes' => $distributorQuoteData->asset_notes,
                    'additional_details' => $distributorQuoteData->additional_details,
                ]
            )->render();

            return;
        }
    }

    private function mapPackTemplateDataControls(WorldwideQuotePreviewData $previewData): void
    {
        $this->hideTemplateElements($previewData);

        $templateData = $previewData->template_data;

        // First page schema
        foreach ($this->getTemplateControlsIterator($templateData->first_page_schema) as $control) {
            $this->mapPackTemplateControl($control, $previewData);
        }

        // Assets page schema
        foreach ($this->getTemplateControlsIterator($templateData->assets_page_schema) as $control) {
            $this->mapPackTemplateControl($control, $previewData);
        }

        // Last page schema
        foreach ($this->getTemplateControlsIterator($templateData->last_page_schema) as $control) {
            $this->mapPackTemplateControl($control, $previewData);
        }
    }

    private function mapPackTemplateControl(
        TemplateElementChildControl $control,
        WorldwideQuotePreviewData $previewData
    ): void
    {
        $templateData = $previewData->template_data;

        if ('img' === $control->type) {
            if (isset($templateData->template_assets->{$control->id})) {
                $control->value = $templateData->template_assets->{$control->id} ?? '';
            }

            return;
        }

        if ('tag' === $control->type) {
            $value = $previewData->quote_summary->{$control->id} ?? '';

            if (str_starts_with($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                    'class' => $control->class,
                    'images' => $templateData->template_assets->{$control->id},
                ])->render();

                return;
            }

            if ('quote_data_aggregation' === $control->id) {
                $control->value = $this->viewFactory->make('ww-quotes.components.quote_data_aggregation', [
                    'aggregation_data' => $previewData->quote_summary->quote_data_aggregation,
                    'aggregation_fields' => $previewData->quote_summary->quote_data_aggregation_fields,
                    'sub_total_value' => $previewData->quote_summary->sub_total_value,
                    'total_value_including_tax' => $previewData->quote_summary->total_value_including_tax,
                    'grand_total_value' => $previewData->quote_summary->grand_total_value,
                    'headers' => $previewData->template_data->headers,
                ])->render();

                return;
            }

            if (is_scalar($value)) {
                $control->value = $value;

                return;
            }

            return;
        }

        if ('quote_assets' === $control->type) {
            $assetsViewName = $previewData->pack_assets_are_grouped
                ? 'ww-quotes.components.grouped_pack_assets'
                : 'ww-quotes.components.pack_assets';

            $control->value = $this->viewFactory->make(
                $assetsViewName,
                [
                    'asset_fields' => $previewData->pack_asset_fields,
                    'assets_data' => $previewData->pack_assets,
                    'asset_notes' => $previewData->asset_notes,
                    'additional_details' => $previewData->quote_summary->additional_details,
                ]
            )->render();

            return;
        }
    }

    /**
     * @return \Iterator|\App\Domain\Template\DataTransferObjects\TemplateElementChildControl[]
     */
    private function getTemplateControlsIterator(array $templatePage): \Iterator
    {
        /** @var \App\Domain\Template\DataTransferObjects\TemplateElement[] $templatePage */
        foreach ($templatePage as $templateElement) {
            foreach ($templateElement->children as $child) {
                foreach ($child->controls as $control) {
                    yield $control;
                }
            }
        }
    }

    private function hideTemplateElements(WorldwideQuotePreviewData $data): void
    {
        foreach ([
                     $data->template_data->first_page_schema,
                     $data->template_data->assets_page_schema,
                     $data->template_data->payment_schedule_page_schema,
                     $data->template_data->last_page_schema,
                 ] as $pageElements) {
            foreach ($pageElements as $element) {
                /** @var TemplateElement $element */
                if (false === $element->toggle) {
                    continue;
                }

                $element->_hidden = false === ($element->visibility xor $data->quote_summary->is_contract_duration_checked);
            }
        }
    }
}
