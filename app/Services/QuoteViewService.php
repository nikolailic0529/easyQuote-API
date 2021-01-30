<?php

namespace App\Services;

use App\Collections\MappedRows;
use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface;
use App\Contracts\Services\QuoteView;
use App\Http\Resources\QuoteResource;
use App\Models\{Quote\BaseQuote, Quote\Discount, Quote\Margin\CountryMargin, Quote\Quote, Quote\QuoteVersion,};
use App\Repositories\Concerns\FetchesGroupDescription;
use Illuminate\Support\{Collection};

class QuoteViewService implements QuoteView
{
    const QUOTE_EXPORT_VIEW = 'quotes.pdf';

    use FetchesGroupDescription;

    protected QuoteSubmittedRepositoryInterface $submittedRepository;

    protected QuoteQueries $quoteQueries;

    public function __construct(QuoteSubmittedRepositoryInterface $submittedRepository, QuoteQueries $quoteQueries)
    {
        $this->submittedRepository = $submittedRepository;
        $this->quoteQueries = $quoteQueries;
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
        $quote->applicableDiscounts = 0;

        /**
         * Possible interaction with Margin percentage.
         */
        $this->interactWithMargin($quote);

        /**
         * Possible interaction with Discounts.
         */

        $this->interact($quote, $quote->discounts);

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
        $marginDivider = $this->getMarginDivider($quote);

        $quote->finalTotalPrice = $quote->totalPrice / $marginDivider;

        if (is_null($quote->custom_discount) || (float)$quote->custom_discount === 0.0) {
            return;
        }

        $marginPercentage = $this->calculateMarginPercentage($quote->totalPrice, (float)$quote->buy_price);

        $bottomUpDivider = 1 - (($marginPercentage - (float)$quote->custom_discount) / 100);

        if ($bottomUpDivider > 0) {
            $buyPriceAfterCustomDiscount = (float)$quote->buy_price / $bottomUpDivider;

            $quote->applicableDiscounts = (float)$quote->totalPrice - $buyPriceAfterCustomDiscount;
        }
    }

    public function getMarginDivider(BaseQuote $quote): float
    {
        /** @var float $countryMarginValue */
        $countryMarginValue = with($quote->countryMargin, function (?CountryMargin $margin): float {
            if (is_null($margin)) {
                return 0.0;
            }

            return (float)$margin->value;
        });

        $marginValue = ($countryMarginValue - (float)$quote->custom_discount) / 100;

        if ($marginValue >= 1) {
            return 1 / ($marginValue + 1);
        }

        return 1 - $marginValue;
    }

    public function calculateMarginPercentage(float $totalPrice, float $buyPrice): float
    {
        if ($totalPrice === 0.0) {
            return 0.0;
        }

        return (($totalPrice - (float)$buyPrice) / $totalPrice) * 100;
    }

    public function applyPredefinedDiscounts(BaseQuote $quote): void
    {
        foreach ($quote->discounts as $discount) {
            $applicableDiscountValue = $this->calculateDiscountValue($discount, $quote->finalTotalPrice, $quote->totalPrice);

            $quote->applicableDiscounts += $applicableDiscountValue;

            $quote->finalTotalPrice -= $applicableDiscountValue;
        }

    }

    public function interactWithDiscount(BaseQuote $quote, $discount)
    {
        if (!isset($quote->computableRows) || (is_numeric($discount) && $discount == 0)) {
            return;
        }

        $applicableDiscountValue = $this->calculateDiscountValue($discount, $quote->totalPrice, $quote->totalPrice);

        $quote->applicableDiscounts += $applicableDiscountValue;

        if (!$discount instanceof Discount) {
            return;
        }

        $listPriceAfterDiscount = $quote->totalPrice - $quote->applicableDiscounts;

        $marginPercentageAfterDiscount = $this->calculateMarginPercentage($listPriceAfterDiscount, $quote->buy_price);

        $discount->margin_percentage = number_format($marginPercentageAfterDiscount, 2, '.', '');
    }

    public function calculateSchedulePrices(BaseQuote $quote)
    {
        if (is_null($quote->scheduleData) || is_null($quote->scheduleData->value)) {
            return;
        }

        $payments = Collection::wrap($quote->scheduleData->value);

        $totalPayments = $payments->reduce(function (float $totalPayments, array $payment) {
            $price = $payment['price'] ?? 0.0;
            $price = is_float($price) ? $price : static::parsePriceValue($price);

            return $totalPayments + $price;
        }, 0.0);

        if ($quote->finalTotalPrice === 0.0) {
            $quote->scheduleData->value = $payments->map(function (array $payment) {
                $payment['price'] = 0.0;

                return $payment;
            });


            return;
        }

        $paymentsPriceCoeff = $quote->finalTotalPrice / $totalPayments;

        $quote->scheduleData->value = $payments->map(function (array $payment) use ($paymentsPriceCoeff) {
            $price = $payment['price'] ?? 0.0;
            $price = is_float($price) ? $price : static::parsePriceValue($price);

            $payment['price'] = $price * $paymentsPriceCoeff;

            return $payment;
        });
    }

    private static function parsePriceValue(string $value): float
    {
        $cleanString = preg_replace('/([^0-9.,])/i', '', $value);
        $onlyNumbersString = preg_replace('/([^0-9])/i', '', $value);

        $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

        $stringWithCommaOrDot = preg_replace('/([,.])/', '', $cleanString, $separatorsCountToBeErased);
        $removedThousandSeparator = preg_replace('/([.,])(?=[0-9]{3,}$)/', '', $stringWithCommaOrDot);

        return (float)str_replace(',', '.', $removedThousandSeparator);
    }

    public function setComputableRows(BaseQuote $quote): void
    {
        $rows = $this->quoteQueries
            ->mappedSelectedRowsQuery($quote, true)
            ->get();

        $quote->computableRows = MappedRows::make($rows);
    }

    public function prepareQuoteReview(BaseQuote $quote)
    {
        $quote->totalPrice = (float)$this->quoteQueries
            ->mappedSelectedRowsQuery($quote)
            ->sum('price');

        $this->setComputableRows($quote);

        $this->interactWithMargin($quote);

        $this->applyPredefinedDiscounts($quote);

        $this->calculateSchedulePrices($quote);

        if ($quote->finalTotalPrice > 0.0) {
            $quote->priceCoef = (float)$quote->target_exchange_rate * ($quote->finalTotalPrice / $quote->totalPrice);
        }

        $quote->buy_price = (float)$quote->target_exchange_rate * (float)$quote->buy_price;

        $this->prepareRows($quote);

        $this->prepareSchedule($quote);

        return $this;
    }

    public function export(BaseQuote $quote, $type = QT_TYPE_QUOTE)
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
        $quote->computableRows->multiplePriceValue($quote->priceCoef);
        $quote->computableRows->exceptHeaders($quote->hiddenFields);
        $quote->renderableRows = (new MappedRows($quote->computableRows))->exceptHeaders($quote->systemHiddenFields);

        if ($quote->groupsReady()) {
            $this->formatGroupDescription($quote);

            return;
        }

        if ($quote->isMode(QT_TYPE_QUOTE)) {
            $this->formatLinePrices($quote);
        }
    }

    public function prepareSchedule(BaseQuote $quote)
    {
        if (is_null($quote->scheduleData) || is_null($quote->scheduleData->value)) {
            return;
        }

        $quote->scheduleData->value = Collection::wrap($quote->scheduleData->value)->map(function (array $payment) use ($quote) {
            $price = $payment['price'] ?? 0.0;

            $price = is_float($price) ? $price : static::parsePriceValue($price);

            $payment['price'] = sprintf("%s %.2f", $quote->currencySymbol, $price);

            return $payment;
        });
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

        $assets = $this->getTemplateAssets($template);

        return compact('data') + $assets;
    }

    protected function getTemplateAssets($template)
    {
        $design = tap($template->form_data, function (&$design) {
            if (isset($design['payment_page'])) {
                $design['payment_schedule'] = $design['payment_page'];
                unset($design['payment_page']);
            }
        });

        $company_logos = $template->company->logoSelection ?? [];
        $vendor_logos = $template->vendor->logoSelection ?? [];
        $images = array_merge($company_logos, $vendor_logos);

        return compact('design', 'images');
    }

    protected function formatGroupDescription(BaseQuote $quote)
    {
        $quote->computableRows = static::mapGroupDescriptionWithRows($quote, $quote->computableRows)
            ->setHeadersCount()
            ->exceptHeaders([...$quote->hiddenFields, ...['group_name']])
            ->setCurrency($quote->currencySymbol);

        $renderHiddenHeaders = $quote->isMode(QT_TYPE_CONTRACT) ? ['total_price'] : [];

        $quote->renderableRows = $quote->computableRows->exceptHeaders([...$quote->systemHiddenFields, ...$renderHiddenHeaders]);
    }

    protected function formatLinePrices(BaseQuote $quote)
    {
        $quote->renderableRows = $quote->renderableRows->setCurrency($quote->currencySymbol);
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
