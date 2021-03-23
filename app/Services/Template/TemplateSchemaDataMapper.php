<?php

namespace App\Services\Template;

use App\Models\Company;
use App\Models\Template\ContractTemplate;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\TemplateSchema;
use App\Models\Vendor;
use App\Services\ThumbHelper;
use Illuminate\Support\Str;

class TemplateSchemaDataMapper
{
    public function mapQuoteTemplateSchema(QuoteTemplate $template): array
    {
        $companyLogo = with($template->company, function (Company $company) {
            if (is_null($company->image)) {
                return [];
            }

            return ThumbHelper::getLogoDimensionsFromImage(
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
            return ThumbHelper::getLogoDimensionsFromImage(
                $vendor->image,
                $vendor->thumbnailProperties(),
                Str::snake(class_basename($vendor))
            );
        }, []);

        $templateSchema = with($template, function (QuoteTemplate $quoteTemplate) {
            if ($quoteTemplate->business_division_id === BD_WORLDWIDE) {
                return TemplateSchema::getPages('ww_quote');
            }

            return TemplateSchema::getPages('quote');
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

            return ThumbHelper::getLogoDimensionsFromImage(
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

        $templateSchema = with($template, function (ContractTemplate $quoteTemplate) {
            if ($quoteTemplate->business_division_id === BD_WORLDWIDE) {
                return TemplateSchema::getPages('ww_quote');
            }

            return TemplateSchema::getPages('quote');
        });

        $templateSchema['first_page'] = array_merge(
            $templateSchema['first_page'],
            $companyLogo,
            $vendorLogo
        );

        return $templateSchema;
    }
}
