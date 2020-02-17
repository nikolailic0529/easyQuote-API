<?php

namespace App\Services;

use App\Contracts\Services\QuoteServiceInterface;
use App\Http\Resources\QuoteResource;
use App\Models\Quote\{
    BaseQuote as Quote,
    Discount,
    Margin\CountryMargin
};
use App\Models\QuoteTemplate\BaseQuoteTemplate;
use Illuminate\Support\Collection;
use Str;

class QuoteService implements QuoteServiceInterface
{
    const QUOTE_EXPORT_VIEW = 'quotes.pdf';

    public function interact(Quote $quote, $interactable): void
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
            collect($interactable)->each(function ($entity) use ($quote) {
                $this->interact($quote, $entity);
            });
        }
    }

    public function interactWithModels(Quote $quote): void
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
    }

    public function interactWithCountryMargin(Quote $quote, CountryMargin $countryMargin): void
    {
        if (!isset($quote->computableRows)) {
            return;
        }

        if ($countryMargin->isPercentage() && $countryMargin->isNoMargin()) {
            $quote->computableRows->transform(function ($row) use ($countryMargin) {
                $row->price = $countryMargin->calculate($row->price);
                return $row;
            });
        }

        if ($countryMargin->isFixed() && $countryMargin->isNoMargin()) {
            $quote->totalPrice = $countryMargin->calculate($quote->totalPrice);
        }
    }

    public function interactWithMargin(Quote $quote): void
    {
        if (!isset($quote->computableRows) || !isset($quote->countryMargin)) {
            return;
        }

        $targetMargin = ($quote->countryMargin->value - $quote->custom_discount) / 100;

        $divider = $targetMargin >= 1
            /**
             * When target margin is greater than or equal to 100% we are reversing bottom up rule.
             * It will be increasing total price and line prices accordingly.
             */
            ? 1 / ($targetMargin + 1)
            /**
             * When target margin is less than 100% we are using default bottom up rule
             * */
            : 1 - $targetMargin;

        $quote->totalPrice = 0.0;

        $quote->computableRows->transform(function ($row) use ($divider, $quote) {
            $row->price = round($row->price / $divider, 2);
            $quote->totalPrice += $row->price;
            return $row;
        });
    }

    public function interactWithDiscount(Quote $quote, $discount): void
    {
        if (!isset($quote->computableRows) || $discount === 0.0) {
            return;
        }

        if (is_numeric($discount)) {
            if ($quote->bottomUpDivider == 0) {
                $quote->totalPrice = 0;
                return;
            }

            $buyPriceAfterDiscount = $quote->buy_price / $quote->bottomUpDivider;
            $quote->applicableDiscounts += $quote->totalPrice - $buyPriceAfterDiscount;

            return;
        }

        $quote->computableRows->transform(function ($row) use ($discount, $quote) {
            if (!isset($row->computablePrice)) {
                $row->computablePrice = $row->price;
            }

            $value = $this->calculateDiscountValue($discount, (float) $row->computablePrice, (float) $quote->totalPrice);
            $quote->applicableDiscounts += $value;
            $row->computablePrice -= $value;

            return $row;
        });

        if (!$discount instanceof Discount) {
            return;
        }

        $listPriceAfterDiscount = $quote->totalPrice - $quote->applicableDiscounts;

        $discount->margin_percentage = (float) $listPriceAfterDiscount != 0
            ? round((($listPriceAfterDiscount - $quote->buy_price) / $listPriceAfterDiscount) * 100, 2)
            : 0;

        return;
    }

    public function calculateSchedulePrices(Quote $quote): void
    {
        if (!isset($quote->scheduleData->value)) {
            return;
        }

        if ($quote->bottomUpDivider == 0) {
            data_set($quote->scheduleData->value, '*.price', 0.0);
            return;
        }

        $targetExchangeRate = $quote->targetExchangeRate;

        $initialTotalPayments = $quote->scheduleData->value->sum('price') * $targetExchangeRate;

        if ($initialTotalPayments == 0) {
            return;
        }

        $reverseMultiplier = $quote->finalPrice / $initialTotalPayments;

        $quote->scheduleData->value = collect($quote->scheduleData->value)->map(function ($payment) use ($targetExchangeRate, $reverseMultiplier) {
            $price = data_get($payment, 'price', 0.0);
            data_set($payment, 'price', Str::price($price) * $targetExchangeRate * $reverseMultiplier);

            return $payment;
        });

        $newTotalPayments = $quote->scheduleData->value
            ->sum(fn ($payment) => round((float) data_get($payment, 'price', 0.0), 2));

        $roundedTotalPayments = round($newTotalPayments, 2);
        $roundedFinalPrice = round($quote->finalPrice, 2);

        if (abs($roundedFinalPrice - $roundedTotalPayments) == 0) {
            return;
        }

        $diffWithFinalPrice = $roundedFinalPrice - $roundedTotalPayments;
        $firstPayment = $quote->scheduleData->value->first();
        $firstPaymentPrice = data_get($firstPayment, 'price', 0.0);
        data_set($firstPayment, 'price', $firstPaymentPrice + $diffWithFinalPrice);

        $quote->scheduleData->value = $quote->scheduleData->value->replace([0 => $firstPayment]);
    }

    public function assignComputableRows(Quote $quote): void
    {
        $quote->computableRows = cache()->sear(
            $quote->computableRowsCacheKey,
            fn () => $quote->getFlattenOrGroupedRows(['where_selected'], $quote->calculate_list_price)
        );

        if (!isset($quote->computableRows->first()->price)) {
            data_fill($quote->computableRows, '*.price', 0.0);
        }
    }

    public function prepareQuoteReview(Quote $quote): void
    {
        $quote->enableExchangeRateConversion();

        $this->assignComputableRows($quote);

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
    }

    public function export(Quote $quote)
    {
        $export = $this->prepareQuoteExport($quote);

        $filename = $this->makePdfFilename($quote);

        return $this->pdfWrapper()
            ->loadView(self::QUOTE_EXPORT_VIEW, $export)
            ->download($filename);
    }

    public function prepareRows(Quote $quote): void
    {
        $keys = $quote->groupsReady()
            ? array_merge($quote->rowsHeaderToArray(), array_flip(['group_name']))
            : $quote->rowsHeaderToArray();

        $quote->computableRows->sortKeysByKeys($keys);

        $quote->computableRows = $quote->computableRows->exceptEach($quote->hiddenFields);
        $quote->renderableRows = $quote->computableRows->exceptEach($quote->systemHiddenFields);

        /**
         * Preventing Empty Rows.
         */
        if (count($quote->computableRows->first() ?? []) === 0) {
            $quote->computableRows = $quote->renderableRows = collect();
        }

        if ($quote->groupsReady()) {
            $this->formatGroupDescription($quote);
            return;
        }

        if ($quote->isMode(QT_TYPE_QUOTE)) {
            $this->formatLinePrices($quote);
        }
    }

    public function prepareSchedule(Quote $quote): void
    {
        if (!isset($quote->scheduleData->value)) {
            return;
        }

        $quote->scheduleData->value = collect($quote->scheduleData->value)
            ->transform(function ($payment) use ($quote) {
                $price = data_get($payment, 'price', 0.0);
                data_set($payment, 'price', Str::prepend(Str::decimal($price, 2), $quote->currencySymbol));

                return $payment;
            });
    }

    protected function prepareQuoteExport(Quote $quote): array
    {
        $this->prepareQuoteReview($quote);

        $resource = QuoteResource::make($quote->enableReview())->resolve();
        $data = to_array_recursive(data_get($resource, 'quote_data', []));

        $template = $quote->{$quote->mode . 'Template'};

        $assets = $this->getTemplateAssets($template);

        return compact('data') + $assets;
    }

    protected function getTemplateAssets(BaseQuoteTemplate $template)
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

    protected function formatGroupDescription(Quote $quote): void
    {
        $groups_meta = $quote->getGroupDescriptionWithMeta(null, $quote->calculate_list_price);
        $quote->computableRows = $quote->computableRows
            ->rowsToGroups('group_name', $groups_meta, true, $quote->currencySymbol)
            ->exceptEach('group_name')
            ->sortByFields($quote->sort_group_description);

        $groupHiddenFields = $quote->isMode(QT_TYPE_CONTRACT) ? ['total_price'] : [];

        $quote->renderableRows = $quote->computableRows->map(function ($group) use ($quote, $groupHiddenFields) {
            $group->forget($groupHiddenFields);

            $rows = $group->get('rows')->exceptEach($quote->systemHiddenFields);
            $group->put('rows', $rows);

            $headers_count = $group->get('headers_count') - count($quote->systemHiddenFields);
            $group->put('headers_count', $headers_count);

            return $group;
        });
    }

    protected function formatLinePrices(Quote $quote): void
    {
        $quote->renderableRows->transform(function ($row) use ($quote) {
            $price = data_get($row, 'price');
            data_set($row, 'price', Str::prepend(Str::decimal($price), $quote->currencySymbol, true));
            return $row;
        });
    }

    protected function interactableDiscounts(Quote $quote): Collection
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

    /**
     * Return PdfWrapper instance.
     *
     * @return \Barryvdh\Snappy\PdfWrapper
     */
    protected function pdfWrapper()
    {
        return app('snappy.pdf.wrapper');
    }

    private function makePdfFilename(Quote $quote): string
    {
        $hash = md5($quote->customer->rfq . time());

        return "{$quote->customer->rfq}_{$hash}.pdf";
    }
}
