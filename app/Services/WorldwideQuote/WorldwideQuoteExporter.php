<?php

namespace App\Services\WorldwideQuote;

use App\DTO\Template\TemplateElement;
use App\DTO\Template\TemplateElementChildControl;
use App\DTO\WorldwideQuote\Export\TemplateData;
use App\DTO\WorldwideQuote\Export\WorldwideDistributionData;
use App\DTO\WorldwideQuote\Export\WorldwideQuotePreviewData;
use App\Events\SalesOrder\SalesOrderExported;
use App\Events\WorldwideQuote\WorldwideQuoteExported;
use App\Foundation\TemporaryDirectory;
use App\Models\Quote\WorldwideQuote;
use App\Models\SalesOrder;
use App\Services\Exceptions\ValidationException;
use App\Services\WorldwideQuote\Models\PageLocatedElements;
use Barryvdh\Snappy\PdfWrapper;
use iio\libmergepdf\Merger;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function with;

class WorldwideQuoteExporter
{

    public function __construct(protected PdfWrapper         $pdfWrapper,
                                protected ViewFactory        $viewFactory,
                                protected ValidatorInterface $validator,
                                protected Dispatcher         $eventDispatcher)
    {
    }

    public function export(WorldwideQuotePreviewData $previewData, WorldwideQuote|SalesOrder $exportedEntity): Response
    {
        $this->mapTemplateDataControls($previewData);

        $rfqNumber = $previewData->quote_summary->export_file_name;

        $event = match ($exportedEntity::class) {
            WorldwideQuote::class => new WorldwideQuoteExported($exportedEntity),
            SalesOrder::class => new SalesOrderExported($exportedEntity),
        };

        $this->eventDispatcher->dispatch($event);

        $tempDir = (new TemporaryDirectory)->create();

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
                ->setOption('margin-bottom', '15')
                ->setOption('footer-html', $this->viewFactory->make('ww-quotes.pdf-footer', ['elements' => $locatedElements->footerElements]))
                ->save($pagePath, true);
        }

        $merger = new Merger();

        foreach ($pagePaths as $path) {
            $merger->addFile($path);
        }

        $content = $merger->merge();

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'."$rfqNumber.pdf".'"',
        ]);
    }

    private function iterateTemplateDataPages(TemplateData $templateData): \Generator
    {
        foreach ($templateData as $page => $elements) {
            if ('template_assets' === $page) {
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
                $footerElements[] = $element;
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

        $controlMapper = function (TemplateElementChildControl $control) use ($templateData, $previewData): void {
            switch ($control->type) {

                case 'img':

                    if (isset($templateData->template_assets->{$control->id})) {
                        $control->value = $templateData->template_assets->{$control->id} ?? '';
                    }

                    break;

                case 'tag':

                    $value = $previewData->quote_summary->{$control->id} ?? '';

                    if (str_starts_with($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                        $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                            'class' => $control->class,
                            'images' => $templateData->template_assets->{$control->id},
                        ])->render();
                    } elseif ($control->id === 'quote_data_aggregation') {
                        $control->value = $this->viewFactory->make('ww-quotes.components.quote_data_aggregation', [
                            'aggregation_data' => $previewData->quote_summary->quote_data_aggregation,
                            'aggregation_fields' => $previewData->quote_summary->quote_data_aggregation_fields,
                            'sub_total_value' => $previewData->quote_summary->sub_total_value,
                            'total_value_including_tax' => $previewData->quote_summary->total_value_including_tax,
                            'grand_total_value' => $previewData->quote_summary->grand_total_value,
                        ])->render();
                    } elseif (is_scalar($value)) {
                        $control->value = $value;
                    }

                    break;
            }
        };

        $distributorQuoteControlMapper = function (TemplateElementChildControl $control, WorldwideDistributionData $distributorQuoteData) use ($templateData, $previewData): void {
            switch ($control->type) {

                case 'img':

                    if (isset($templateData->template_assets->{$control->id})) {
                        $control->value = $templateData->template_assets->{$control->id} ?? '';
                    }

                    break;

                case 'tag':

                    $value = $distributorQuoteData->{$control->id} ?? $previewData->quote_summary->{$control->id} ?? '';

                    if (str_starts_with($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                        $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                            'class' => $control->class,
                            'images' => $templateData->template_assets->{$control->id},
                        ])->render();
                    } elseif ($control->id === 'quote_data_aggregation') {
                        $control->value = $this->viewFactory->make('ww-quotes.components.quote_data_aggregation', [
                            'aggregation_data' => $previewData->quote_summary->quote_data_aggregation,
                            'aggregation_fields' => $previewData->quote_summary->quote_data_aggregation_fields,
                            'sub_total_value' => $previewData->quote_summary->sub_total_value,
                            'total_value_including_tax' => $previewData->quote_summary->total_value_including_tax,
                            'grand_total_value' => $previewData->quote_summary->grand_total_value,
                        ])->render();
                    } elseif (is_scalar($value)) {
                        $control->value = $value;
                    }

                    break;
            }
        };

        // First page schema
        foreach ($this->getTemplateControlsIterator($templateData->first_page_schema) as $control) {
            $controlMapper($control);
        }

        $assetsPageSchema = [];

        foreach ($previewData->distributions as $distribution) {
            $distributionAssetsSchema = $templateData->assets_page_schema;

            foreach ($this->getTemplateControlsIterator($distributionAssetsSchema) as $control) {
                $distributorQuoteControlMapper($control, $distribution);
            }

            $assetsElement = with(true, function () use ($distribution) {

                $assetsViewName = $distribution->assets_are_grouped
                    ? 'ww-quotes.components.distribution_grouped_assets'
                    : 'ww-quotes.components.distribution_assets';

                $assetsView = $this->viewFactory->make(
                    $assetsViewName,
                    ['asset_fields' => $distribution->asset_fields, 'assets_data' => $distribution->assets_data, 'asset_notes' => $distribution->asset_notes]
                );

                $children = with([], function (array $children) use ($distribution, $assetsView) {

                    $children[] = [
                        'id' => (string)Str::uuid(),
                        'class' => 'col-lg-12',
                        'controls' => [
                            [
                                'id' => 'distribution_assets_data',
                                'type' => 'tag',
                                'class' => '',
                                'css' => '',
                                'value' => $assetsView->render(),
                            ],
                        ],
                    ];

                    if (is_string($distribution->additional_details) && trim(strip_tags($distribution->additional_details)) !== '') {
                        $children[] = [
                            'id' => (string)Str::uuid(),
                            'class' => 'col-lg-12 mt-2',
                            'controls' => [
                                [
                                    'id' => 'additional_details',
                                    'type' => 'tag',
                                    'class' => '',
                                    'value' => $distribution->additional_details,
                                ],
                            ],
                        ];
                    }

                    return $children;

                });

                return new TemplateElement([
                    'children' => $children,
                    'class' => '',
                    'css' => '',
                ]);

            });

            $distributionAssetsSchema[] = $assetsElement;
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

            $assetsElement = with(true, function () use ($distribution) {

                $assetsView = $this->viewFactory->make(
                    'ww-quotes.components.distribution_payments',
                    ['payment_schedule_fields' => $distribution->payment_schedule_fields, 'payment_schedule_data' => $distribution->payment_schedule_data]
                );

                $controls = with([], function (array $controls) use ($assetsView, $distribution) {
                    $controls[] = [
                        'id' => (string)Str::uuid(),
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
                            'id' => (string)Str::uuid(),
                            'class' => 'col-lg-12',
                            'controls' => $controls,
                        ],
                        [
                            'id' => (string)Str::uuid(),
                            'class' => 'page-break',
                            'controls' => [],
                        ],
                    ],
                    'class' => '',
                    'css' => '',
                ]);

            });

            $distributionPaymentsSchema[] = $assetsElement;

            $paymentsPageSchema = array_merge($paymentsPageSchema, $distributionPaymentsSchema);
        }

        $templateData->payment_schedule_page_schema = $paymentsPageSchema;


        // Last page schema

        foreach ($this->getTemplateControlsIterator($templateData->last_page_schema) as $control) {
            $controlMapper($control);
        }
    }

    private function mapPackTemplateDataControls(WorldwideQuotePreviewData $previewData): void
    {
        $this->hideTemplateElements($previewData);

        $templateData = $previewData->template_data;

        $controlMapper = function (TemplateElementChildControl $control) use ($templateData, $previewData): void {
            switch ($control->type) {

                case 'img':

                    if (isset($templateData->template_assets->{$control->id})) {
                        $control->value = $templateData->template_assets->{$control->id} ?? '';
                    }

                    break;

                case 'tag':

                    $value = $previewData->quote_summary->{$control->id} ?? '';

                    if (str_starts_with($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                        $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                            'class' => $control->class,
                            'images' => $templateData->template_assets->{$control->id},
                        ])->render();
                    } elseif ($control->id === 'quote_data_aggregation') {
                        $control->value = $this->viewFactory->make('ww-quotes.components.quote_data_aggregation', [
                            'aggregation_data' => $previewData->quote_summary->quote_data_aggregation,
                            'aggregation_fields' => $previewData->quote_summary->quote_data_aggregation_fields,
                            'sub_total_value' => $previewData->quote_summary->sub_total_value,
                            'total_value_including_tax' => $previewData->quote_summary->total_value_including_tax,
                            'grand_total_value' => $previewData->quote_summary->grand_total_value,
                        ])->render();
                    } elseif (is_scalar($value)) {
                        $control->value = $value;
                    }

                    break;
            }
        };

        // Render & append Pack Assets table to the assets_page_schema.

        // First page schema
        foreach ($this->getTemplateControlsIterator($templateData->first_page_schema) as $control) {
            $controlMapper($control);
        }

        // Assets page schema
        foreach ($this->getTemplateControlsIterator($templateData->assets_page_schema) as $control) {
            $controlMapper($control);
        }

        with($previewData->template_data, function (TemplateData $templateData) use ($previewData) {
            $assetsElement = with(true, function () use ($previewData) {

                $assetsViewName = $previewData->pack_assets_are_grouped
                    ? 'ww-quotes.components.grouped_pack_assets'
                    : 'ww-quotes.components.pack_assets';

                $assetsView = $this->viewFactory->make(
                    $assetsViewName,
                    ['asset_fields' => $previewData->pack_asset_fields, 'assets_data' => $previewData->pack_assets, 'asset_notes' => $previewData->asset_notes]
                );

                $children = with([], function (array $children) use ($previewData, $assetsView) {

                    $children[] = [
                        'id' => (string)Str::uuid(),
                        'class' => 'col-lg-12',
                        'controls' => [
                            [
                                'id' => 'pack_assets',
                                'type' => 'tag',
                                'class' => '',
                                'css' => '',
                                'value' => $assetsView->render(),
                            ],
                        ],
                    ];

                    if (is_string($previewData->quote_summary->additional_details) && trim(strip_tags($previewData->quote_summary->additional_details)) !== '') {
                        $children[] = [
                            'id' => (string)Str::uuid(),
                            'class' => 'col-lg-12 mt-2',
                            'controls' => [
                                [
                                    'id' => 'additional_details',
                                    'type' => 'tag',
                                    'class' => '',
                                    'value' => $previewData->quote_summary->additional_details,
                                ],
                            ],
                        ];
                    }

                    $children[] = [
                        'id' => (string)Str::uuid(),
                        'class' => 'page-break',
                        'controls' => [],
                    ];

                    return $children;

                });

                return new TemplateElement([
                    'children' => $children,
                    'class' => '',
                    'css' => '',
                ]);

            });

            $previewData->template_data->assets_page_schema[] = $assetsElement;
        });

        // Last page schema

        foreach ($this->getTemplateControlsIterator($templateData->last_page_schema) as $control) {
            $controlMapper($control);
        }
    }

    /**
     * @param array $templatePage
     * @return \Iterator|\App\DTO\Template\TemplateElementChildControl[]
     */
    private function getTemplateControlsIterator(array $templatePage): \Iterator
    {
        /** @var \App\DTO\Template\TemplateElement[] $templatePage */
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
