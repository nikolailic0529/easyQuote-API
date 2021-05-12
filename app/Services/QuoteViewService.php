<?php

namespace App\Services;

use App\Collections\MappedRows;
use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface;
use App\Contracts\Services\QuoteView;
use App\Http\Resources\QuoteResource;
use App\Models\{Company,
    Quote\BaseQuote,
    Quote\Discount,
    Quote\Margin\CountryMargin,
    Quote\Quote,
    Quote\QuoteVersion,
    Template\ContractTemplate,
    Template\QuoteTemplate,
    Vendor};
use App\Queries\QuoteQueries;
use App\Repositories\Concerns\FetchesGroupDescription;
use Illuminate\Support\{Collection, Str};
use Illuminate\Http\Response;

class QuoteViewService implements QuoteView
{
    use FetchesGroupDescription;

    const QUOTE_EXPORT_VIEW = 'quotes.pdf';

    protected QuoteSubmittedRepositoryInterface $submittedRepository;

    public function __construct(QuoteSubmittedRepositoryInterface $submittedRepository)
    {
        $this->submittedRepository = $submittedRepository;
    }

    public function requestForQuote(string $RFQnumber, string $clientName = null): BaseQuote
    {
        /** @var Quote */
        $quote = $this->submittedRepository->findByRfq($RFQnumber);

        if ($clientName) {
            activity()->on($quote)->causedByService($clientName)->queue('retrieved');
        }

        $version = $quote->activeVersionOrCurrent;

        $this->prepareQuoteReview($version);

        return $version;
    }

    public function interact(BaseQuote $quote, $interactable): void
    {
        if ($interactable instanceof CountryMargin) {
            $this->interactWithCountryMargin($quote, $interactable);
            return;
        }

        if ($interactable instanceof Discount || is_numeric($interactable)) {
            $this->interactWithDiscount($quote, $interactable);
            return;
        }

        if (is_iterable($interactable)) {
            Collection::wrap($interactable)->each(fn($entity) => $this->interact($quote, $entity));
        }
    }

    public function interactWithModels(BaseQuote $quote)
    {
        /**
         * Possible interaction with Margin percentage.
         */
        $this->interactWithMargin($quote);

        /**
         * Possible interaction with Discounts.
         */
        $quote->applicableDiscounts = 0;

        $this->interact($quote, $this->interactableDiscounts($quote));

        return $this;
    }

    public function interactWithCountryMargin(BaseQuote $quote, CountryMargin $countryMargin)
    {
        if (!isset($quote->computableRows)) {
            return $this;
        }

        if ($countryMargin->isPercentage() && $countryMargin->isNoMargin()) {
            $quote->computableRows->transform(function ($row) use ($countryMargin) {
                $row->price = $countryMargin->calculate($row->price ?? 0);
                return $row;
            });
        }

        if ($countryMargin->isFixed() && $countryMargin->isNoMargin()) {
            $quote->totalPrice = $countryMargin->calculate($quote->totalPrice);
        }

        return $this;
    }

    public function interactWithMargin(BaseQuote $quote)
    {
        if (!isset($quote->computableRows) || !isset($quote->countryMargin)) {
            return $this;
        }

        $divider = $quote->margin_divider;

        $quote->totalPrice = 0.0;

        $quote->computableRows->transform(function ($row) use ($divider, $quote) {
            $row->price = round($row->price ?? 0 / $divider, 2);
            $quote->totalPrice += $row->price;
            return $row;
        });

        return $this;
    }

    public function interactWithDiscount(BaseQuote $quote, $discount)
    {
        if (!isset($quote->computableRows) || (is_numeric($discount) && $discount == 0)) {
            return $this;
        }

        if (is_numeric($discount)) {
            if ($quote->bottomUpDivider == 0) {
                $quote->totalPrice = 0;
                return $this;
            }

            $buyPriceAfterDiscount = $quote->buy_price / $quote->bottomUpDivider;
            $quote->applicableDiscounts += $quote->totalPrice - $buyPriceAfterDiscount;

            return $this;
        }

        $quote->computableRows->transform(function ($row) use ($discount, $quote) {
            if (!isset($row->computablePrice)) {
                $row->computablePrice = $row->price;
            }

            $value = $this->calculateDiscountValue($discount, (float)$row->computablePrice, (float)$quote->totalPrice);
            $quote->applicableDiscounts += $value;
            $row->computablePrice -= $value;

            return $row;
        });

        if (!$discount instanceof Discount) {
            return $this;
        }

        $listPriceAfterDiscount = $quote->totalPrice - $quote->applicableDiscounts;

        $discount->margin_percentage = (float)$listPriceAfterDiscount != 0
            ? round((($listPriceAfterDiscount - $quote->buy_price) / $listPriceAfterDiscount) * 100, 2) : 0;

        return $this;
    }

    public function calculateSchedulePrices(BaseQuote $quote)
    {
        if (!isset($quote->scheduleData->value)) {
            return $this;
        }

        if ($quote->bottomUpDivider == 0) {
            data_set($quote->scheduleData->value, '*.price', 0.0);
            return $this;
        }

        $targetExchangeRate = $quote->targetExchangeRate;

        /** @var \Illuminate\Support\Collection */
        $payments = Collection::wrap($quote->scheduleData->value);

        $payments = $payments->map(fn($payment) => data_set($payment, 'price', (float)data_get($payment, 'price')));

        $initialTotalPayments = $payments->sum('price') * $targetExchangeRate;

        if ($initialTotalPayments == 0) {
            return $this;
        }

        $reverseMultiplier = $quote->finalPrice / $initialTotalPayments;

        $quote->scheduleData->value = $payments->map(function ($payment) use ($targetExchangeRate, $reverseMultiplier) {
            $price = data_get($payment, 'price', 0.0);
            return data_set($payment, 'price', Str::price($price) * $targetExchangeRate * $reverseMultiplier);
        });

        return $this;
    }

    public function setComputableRows(BaseQuote $quote)
    {
        $rows = (new QuoteQueries)
            ->mappedSelectedRowsQuery($quote, true)
            ->get();

        $quote->computableRows = MappedRows::make($rows);

        return $this;
    }

    public function prepareQuoteReview(BaseQuote $quote)
    {
        $quote->enableExchangeRateConversion();

        $this->setComputableRows($quote);

        /**
         * Possible Interactions with Margins and Discounts
         */
        $this->interactWithModels($quote);

        /**
         * Calculate Schedule Total Prices based on Margin Percentage
         */
        $this->calculateSchedulePrices($quote);

        $this->prepareRows($quote);

        $this->prepareSchedule($quote);

        return $this;
    }

    /**
     * @param BaseQuote $quote
     * @param int $type
     * @return Response
     */
    public function export(BaseQuote $quote, int $type = QT_TYPE_QUOTE): Response
    {
        $quote->switchModeTo($type);

        $export = $this->prepareQuoteExport($quote);

        $filename = $this->makePdfFilename($quote);

        return app('snappy.pdf.wrapper')
            ->loadView(self::QUOTE_EXPORT_VIEW, $export)
            ->download($filename);
    }

    public function prepareRows(BaseQuote $quote)
    {
        $quote->computableRows = $quote->computableRows->exceptHeaders($quote->hiddenFields);
        $quote->renderableRows = $quote->computableRows->exceptHeaders($quote->systemHiddenFields);

        if ($quote->groupsReady()) {
            $this->formatGroupDescription($quote);
            return $this;
        }

        if ($quote->isMode(QT_TYPE_QUOTE)) {
            $this->formatLinePrices($quote);
        }

        return $this;
    }

    public function prepareSchedule(BaseQuote $quote)
    {
        if (!isset($quote->scheduleData->value)) {
            return $this;
        }

        $quote->scheduleData->value = MappedRows::make($quote->scheduleData->value)->setCurrency($quote->currencySymbol);

        return $this;
    }

    protected function prepareQuoteExport(BaseQuote $baseQuote): array
    {
        $this->prepareQuoteReview($baseQuote);

        $resource = QuoteResource::make($baseQuote->enableReview())->resolve();
        $data = to_array_recursive(data_get($resource, 'quote_data', []));

        if ($baseQuote->isMode(QT_TYPE_CONTRACT)) {
            $template = $baseQuote instanceof QuoteVersion && $baseQuote->quote->exists
                ? $baseQuote->quote->contractTemplate
                : $baseQuote->contractTemplate;
        } else {
            $template = $baseQuote->quoteTemplate;
        }

        return ['data' => $data] +
            $this->getTemplateAssets($template);
    }


    /**
     * @param QuoteTemplate|ContractTemplate $template
     * @return array
     */
    protected function getTemplateAssets($template): array
    {
        $design = tap($template->form_data, function (&$design) {
            if (isset($design['payment_page'])) {
                $design['payment_schedule'] = $design['payment_page'];
                unset($design['payment_page']);
            }
        });

        $companyLogo = with($template->company, function (Company $company) {
            if (is_null($company->image)) {
                return [];
            }

            return ThumbHelper::getLogoDimensionsFromImage(
                $company->image,
                $company->thumbnailProperties(),
                Str::snake(class_basename($company)),
                ThumbHelper::ABS_PATH | ThumbHelper::WITH_KEYS
            );
        });

        $vendorLogo = with($template, function ($template) {
            if (!$template instanceof QuoteTemplate) {

                if (!is_null($template->vendor->image)) {
                    return [];
                }

                return ThumbHelper::getLogoDimensionsFromImage(
                    $template->vendor->image,
                    $template->vendor->thumbnailProperties(),
                    Str::snake(class_basename($template->vendor)),
                    ThumbHelper::ABS_PATH | ThumbHelper::WITH_KEYS
                );
            }

            $logo = $template->vendors->map(function (Vendor $vendor, int $key) {
                if (is_null($vendor->image)) {
                    return [];
                }

                return ThumbHelper::getLogoDimensionsFromImage(
                    $vendor->image,
                    $vendor->thumbnailProperties(),
                    Str::snake(class_basename($vendor)).'_'.++$key,
                    ThumbHelper::ABS_PATH | ThumbHelper::WITH_KEYS
                );
            })->collapse()->all();

            $vendorLogoForBackCompatibility = transform($template->vendors->first(), function (Vendor $vendor) {
                return ThumbHelper::getLogoDimensionsFromImage(
                    $vendor->image,
                    $vendor->thumbnailProperties(),
                    Str::snake(class_basename($vendor)),
                    ThumbHelper::ABS_PATH | ThumbHelper::WITH_KEYS
                );
            }, []);

            return array_merge($vendorLogoForBackCompatibility, $logo);
        });

        return [
            'design' => $design,
            'images' => array_merge($companyLogo, $vendorLogo),
        ];
    }

    protected function formatGroupDescription(BaseQuote $quote)
    {
        $quote->computableRows = static::mapGroupDescriptionWithRows($quote, $quote->computableRows)
            ->setHeadersCount()
            ->exceptHeaders([...$quote->hiddenFields, ...['group_name']])
            ->setCurrency($quote->currencySymbol);

        $renderHiddenHeaders = $quote->isMode(QT_TYPE_CONTRACT) ? ['total_price'] : [];

        $quote->renderableRows = $quote->computableRows->exceptHeaders([...$quote->systemHiddenFields, ...$renderHiddenHeaders]);

        return $this;
    }

    protected function formatLinePrices(BaseQuote $quote)
    {
        $quote->renderableRows = $quote->renderableRows->setCurrency($quote->currencySymbol);

        return $this;
    }

    protected function interactableDiscounts(BaseQuote $quote): Collection
    {
        if (filled($quote->discounts)) {
            return collect($quote->discounts);
        }

        if ($quote->custom_discount > 0) {
            return collect($quote->custom_discount);
        }

        return collect();
    }

    protected function calculateDiscountValue($discount, float $price, float $list_price): float
    {
        if ($discount instanceof Discount) {
            return $discount->calculateDiscount($price, $list_price);
        }

        if (is_numeric($discount)) {
            return $price * $discount / 100;
        }

        return 0.0;
    }

    private function makePdfFilename(BaseQuote $quote): string
    {
        $hash = md5($quote->customer->rfq.time());

        return "{$quote->customer->rfq}_{$hash}.pdf";
    }
}
