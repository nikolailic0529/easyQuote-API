<?php

namespace App\Services;

use App\DTO\Template\TemplateElement;
use App\DTO\WorldwideQuote\Export\TemplateData;
use App\DTO\WorldwideQuote\Export\WorldwideQuotePreviewData;
use App\Services\Exceptions\ValidationException;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WorldwideQuoteExporter
{
    protected PdfWrapper $pdfWrapper;

    protected ViewFactory $viewFactory;

    protected ValidatorInterface $validator;

    public function __construct(PdfWrapper $pdfWrapper, ViewFactory $viewFactory, ValidatorInterface $validator)
    {
        $this->pdfWrapper = $pdfWrapper;
        $this->viewFactory = $viewFactory;
        $this->validator = $validator;
    }

    public function export(WorldwideQuotePreviewData $previewData): Response
    {
        $this->mapTemplateDataControls($previewData);

        $rfqNumber = $previewData->quote_summary->export_file_name;

        return $this->pdfWrapper
            ->loadView(
                'ww-quotes.pdf-export',
                ['template_data' => $previewData->template_data]
            )
            ->download("$rfqNumber.pdf");
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
        $templateData = $previewData->template_data;

        $violations = $this->validator->validate($previewData);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        if ($previewData->contract_type_name === 'Pack') {
            $this->mapPackTemplateDataControls($previewData);
        } elseif ($previewData->contract_type_name === 'Contract') {
            $this->mapContractTemplateDataControls($previewData);
        }

    }


    private function mapContractTemplateDataControls(WorldwideQuotePreviewData $previewData): void
    {
        $templateData = $previewData->template_data;

        // First page schema
        foreach ($this->getTemplateControlsIterator($templateData->first_page_schema) as $control) {

            switch ($control->type) {

                case 'img':
//                    $control->value = $templateData->template_assets[$control->id] ?? '';
                    break;

                case 'tag':
                    $value = $previewData->quote_summary->{$control->id} ?? '';

                    if (Str::startsWith($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                        $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                            'images' => $templateData->template_assets->{$control->id}
                        ]);
                    } elseif (is_scalar($value)) {
                        $control->value = $value;
                    } elseif ($control->id === 'quote_data_aggregation') {
                        $control->value = $this->viewFactory->make('ww-quotes.components.quote_data_aggregation', [
                            'aggregation_data' => $previewData->quote_summary->quote_data_aggregation,
                            'aggregation_fields' => $previewData->quote_summary->quote_data_aggregation_fields,
                            'sub_total_value' => $previewData->quote_summary->sub_total_value,
                            'total_value_including_tax' => $previewData->quote_summary->total_value_including_tax,
                            'grand_total_value' => $previewData->quote_summary->grand_total_value,
                        ])->render();
                    }

                    break;
            }

        }

        // Assets page schema
        foreach ($this->getTemplateControlsIterator($templateData->assets_page_schema) as $control) {
            switch ($control->type) {

                case 'img':
//                    $control->value = $templateData->template_assets[$control->id] ?? '';
                    break;

                case 'tag':
                    $value = $previewData->quote_summary->{$control->id} ?? $previewData->distributions[0]->{$control->id} ?? '';

                    if (Str::startsWith($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                        $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                            'images' => $templateData->template_assets->{$control->id}
                        ]);
                    } elseif (is_scalar($value)) {
                        $control->value = $value;
                    }

                    break;
            }
        }

        $assetsPageSchema = [];

        foreach ($previewData->distributions as $distribution) {
            $distributionAssetsSchema = $templateData->assets_page_schema;

            $assetsElement = with(true, function () use ($distribution) {

                $assetsViewName = $distribution->assets_are_grouped
                    ? 'ww-quotes.components.distribution_grouped_assets'
                    : 'ww-quotes.components.distribution_assets';

                $assetsView = $this->viewFactory->make(
                    $assetsViewName,
                    ['asset_fields' => $distribution->asset_fields, 'assets_data' => $distribution->assets_data]
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
                'css' => ''
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
            switch ($control->type) {

                case 'img':
//                    $control->value = $templateData->template_assets[$control->id] ?? '';
                    break;

                case 'tag':
                    $value = $previewData->quote_summary->{$control->id} ?? '';

                    if (Str::startsWith($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                        $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                            'images' => $templateData->template_assets->{$control->id}
                        ]);
                    } elseif (is_scalar($value)) {
                        $control->value = $value;
                    }

                    break;
            }
        }
    }

    private function mapPackTemplateDataControls(WorldwideQuotePreviewData $previewData): void
    {
        $templateData = $previewData->template_data;

        // Render & append Pack Assets table to the assets_page_schema.

        // First page schema
        foreach ($this->getTemplateControlsIterator($templateData->first_page_schema) as $control) {

            switch ($control->type) {

                case 'img':
//                    $control->value = $templateData->template_assets[$control->id] ?? '';
                    break;

                case 'tag':
                    $value = $previewData->quote_summary->{$control->id} ?? '';

                    if (Str::startsWith($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                        $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                            'images' => $templateData->template_assets->{$control->id}
                        ]);
                    } elseif (is_scalar($value)) {
                        $control->value = $value;
                    } elseif ($control->id === 'quote_data_aggregation') {
                        $control->value = $this->viewFactory->make('ww-quotes.components.quote_data_aggregation', [
                            'aggregation_data' => $previewData->quote_summary->quote_data_aggregation,
                            'aggregation_fields' => $previewData->quote_summary->quote_data_aggregation_fields,
                            'sub_total_value' => $previewData->quote_summary->sub_total_value,
                            'total_value_including_tax' => $previewData->quote_summary->total_value_including_tax,
                            'grand_total_value' => $previewData->quote_summary->grand_total_value,
                        ])->render();
                    }

                    break;
            }

        }

        // Assets page schema
        foreach ($this->getTemplateControlsIterator($templateData->assets_page_schema) as $control) {
            switch ($control->type) {

                case 'img':
//                    $control->value = $templateData->template_assets[$control->id] ?? '';
                    break;

                case 'tag':
                    $value = $previewData->quote_summary->{$control->id} ?? $previewData->distributions[0]->{$control->id} ?? '';

                    if (Str::startsWith($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                        $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                            'images' => $templateData->template_assets->{$control->id}
                        ]);
                    } elseif (is_scalar($value)) {
                        $control->value = $value;
                    }

                    break;
            }
        }

        with($previewData->template_data, function (TemplateData $templateData) use ($previewData) {
            $assetsElement = with(true, function () use ($previewData) {

                $assetsViewName = 'ww-quotes.components.pack_assets';

                $assetsView = $this->viewFactory->make(
                    $assetsViewName,
                    ['asset_fields' => $previewData->pack_asset_fields, 'assets_data' => $previewData->pack_assets]
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
            switch ($control->type) {

                case 'img':
                    $control->value = $templateData->template_assets[$control->id] ?? '';
                    break;

                case 'tag':
                    $value = $previewData->quote_summary->{$control->id} ?? '';

                    if (Str::startsWith($control->id, 'logo_set_x') && isset($templateData->template_assets->{$control->id})) {
                        $control->value = $this->viewFactory->make('ww-quotes.components.images_row', [
                            'images' => $templateData->template_assets->{$control->id}
                        ]);
                    } elseif (is_scalar($value)) {
                        $control->value = $value;
                    }

                    break;
            }
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
}
