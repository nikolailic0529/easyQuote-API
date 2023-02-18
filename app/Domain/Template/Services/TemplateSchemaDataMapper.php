<?php

namespace App\Domain\Template\Services;

use App\Domain\Company\Models\Company;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\Services\Models\TemplateControlFilter;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
use Illuminate\Support\Str;

class TemplateSchemaDataMapper
{
    public function mapQuoteTemplateSchema(QuoteTemplate $template): array
    {
        $companyLogo = with($template->company, function (Company $company) {
            if (is_null($company->image)) {
                return [];
            }

            return \App\Domain\Image\Services\ThumbHelper::getLogoDimensionsFromImage(
                $company->image,
                $company->thumbnailProperties(),
                Str::snake(class_basename($company))
            );
        });

        $vendorLogo = $template->vendors->map(function (Vendor $vendor, int $key) {
            if (is_null($vendor->image)) {
                return [];
            }

            return ThumbHelper::getLogoDimensionsFromImage(
                $vendor->image,
                $vendor->thumbnailProperties(),
                Str::snake(class_basename($vendor)).'_'.++$key
            );
        })->collapse()->all();

        $vendorLogoForBackCompatibility = transform($template->vendors->first(), function (Vendor $vendor) {
            return \App\Domain\Image\Services\ThumbHelper::getLogoDimensionsFromImage(
                $vendor->image,
                $vendor->thumbnailProperties(),
                Str::snake(class_basename($vendor))
            );
        }, []);

        $templateSchema = with($template, function (QuoteTemplate $quoteTemplate) {
            if ($quoteTemplate->business_division_id === BD_WORLDWIDE) {
                return \App\Domain\Template\Models\TemplateForm::getPages('ww_quote');
            }

            return \App\Domain\Template\Models\TemplateForm::getPages('quote');
        });

        $templateSchema['first_page'] = array_merge(
            $templateSchema['first_page'],
            $companyLogo,
            $vendorLogo,
            $vendorLogoForBackCompatibility
        );

        return $templateSchema;
    }

    public function mapContractTemplateSchema(ContractTemplate $template): array
    {
        $companyLogo = with($template->company, function (Company $company) {
            if (is_null($company->image)) {
                return [];
            }

            return \App\Domain\Image\Services\ThumbHelper::getLogoDimensionsFromImage(
                $company->image,
                $company->thumbnailProperties(),
                Str::snake(class_basename($company))
            );
        });

        $vendorLogo = transform($template->vendor, function (Vendor $vendor) {
            return \App\Domain\Image\Services\ThumbHelper::getLogoDimensionsFromImage(
                $vendor->image,
                $vendor->thumbnailProperties(),
                Str::snake(class_basename($vendor))
            );
        }, []);

        $templateSchema = with($template, function (ContractTemplate $quoteTemplate) {
            if ($quoteTemplate->business_division_id === BD_WORLDWIDE) {
                return \App\Domain\Template\Models\TemplateForm::getPages('ww_quote');
            }

            return \App\Domain\Template\Models\TemplateForm::getPages('quote');
        });

        $templateSchema['first_page'] = array_merge(
            $templateSchema['first_page'],
            $companyLogo,
            $vendorLogo
        );

        return $templateSchema;
    }

    public function mapSalesOrderTemplateSchema(SalesOrderTemplate $template): array
    {
        $companyLogo = with($template->company, function (Company $company) {
            if (is_null($company->image)) {
                return [];
            }

            return \App\Domain\Image\Services\ThumbHelper::getLogoDimensionsFromImage(
                $company->image,
                $company->thumbnailProperties(),
                Str::snake(class_basename($company))
            );
        });

        $vendorLogo = transform($template->vendor, function (Vendor $vendor) {
            return ThumbHelper::getLogoDimensionsFromImage(
                $vendor->image,
                $vendor->thumbnailProperties(),
                Str::snake(class_basename($vendor))
            );
        }, []);

        $templateSchema = with($template, function (SalesOrderTemplate $salesOrderTemplate) {
            if ($salesOrderTemplate->business_division_id === BD_WORLDWIDE) {
                return \App\Domain\Template\Models\TemplateForm::getPages('ww_quote');
            }

            return \App\Domain\Template\Models\TemplateForm::getPages('quote');
        });

        $templateSchema['first_page'] = array_merge(
            $templateSchema['first_page'],
            $companyLogo,
            $vendorLogo
        );

        return $templateSchema;
    }

    public function setControlValue(TemplateControlFilter $controlFilter, mixed $value, array &$template, int $limit = null): void
    {
        $count = 0;

        $limit = is_int($limit) ? abs($limit) : null;
        $limit = 0 === $limit ? 1 : $limit;

        foreach ($template as $colIndex => $col) {
            foreach ($col['child'] as $rowIndex => $row) {
                for ($i = 0; $i < count($row['controls']); ++$i) {
                    if ($controlFilter->isSatisfied($i, $row['controls'])) {
                        $template[$colIndex]['child'][$rowIndex]['controls'][$i]['value'] = $value;
                        ++$count;
                    }

                    if (null !== $limit && $count === $limit) {
                        return;
                    }
                }
            }
        }
    }
}
