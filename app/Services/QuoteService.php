<?php

namespace App\Services;

use App\Contracts\{
    Services\QuoteServiceInterface,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository
};
use App\Http\Resources\QuoteResource;
use App\Models\Quote\{
    Quote,
    Discount,
    Margin\CountryMargin
};
use Illuminate\Support\Collection;
use Storage, Closure, Str;

class QuoteService implements QuoteServiceInterface
{
    protected $quoteFile;

    public function __construct(QuoteFileRepository $quoteFile)
    {
        $this->quoteFile = $quoteFile;
    }

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
        $quote->applicable_discounts = 0;

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

        $divider = (100 - ($quote->countryMargin->value - $quote->custom_discount)) / 100;

        if ((float) $divider === 0.0) {
            data_fill($quote->computableRows, '*.price', 0.0);

            $quote->totalPrice = 0.0;
            return;
        }

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
            if ($quote->bottomUpDivider === 0.0) {
                $quote->totalPrice = 0.0;
                return;
            }

            $buyPriceAfterDiscount = $quote->buy_price / $quote->bottomUpDivider;
            $quote->applicable_discounts += $quote->totalPrice - $buyPriceAfterDiscount;

            return;
        }

        $quote->computableRows->transform(function ($row) use ($discount, $quote) {
            if (!isset($row->computablePrice)) {
                $row->computablePrice = $row->price;
            }

            $value = $this->calculateDiscountValue($discount, (float) $row->computablePrice, (float) $quote->totalPrice);
            $quote->applicable_discounts += $value;
            $row->computablePrice -= $value;

            return $row;
        });

        if (!$discount instanceof Discount) {
            return;
        }

        $listPriceAfterDiscount = $quote->totalPrice - $quote->applicable_discounts;

        $discount->margin_percentage = (float) $listPriceAfterDiscount !== 0.0
            ? round((($listPriceAfterDiscount - $quote->buy_price) / $listPriceAfterDiscount) * 100, 2)
            : 0.0;

        return;
    }

    public function calculateSchedulePrices(Quote $quote): void
    {
        if (!isset($quote->scheduleData->value)) {
            return;
        }

        if ((float) $quote->bottomUpDivider === 0.0) {
            data_set($quote->scheduleData->value, '*.price', 0.0);

            return;
        }

        $initialTotalPayments = $quote->scheduleData->value->sum('price');

        if ((float) $initialTotalPayments === 0) {
            return;
        }

        $reverseMultiplier = $quote->finalPrice / $initialTotalPayments;

        $quote->scheduleData->value = collect($quote->scheduleData->value)->map(function ($payment) use ($reverseMultiplier) {
            $price = data_get($payment, 'price', 0.0);
            data_set($payment, 'price', Str::price($price) * $reverseMultiplier);

            return $payment;
        });

        $newTotalPayments = $quote->scheduleData->value->sum('price');

        $roundedTotalPayments = round($newTotalPayments, 3);
        $roundedFinalPrice = round($quote->finalPrice, 3);

        if ((float) abs($roundedFinalPrice - $roundedTotalPayments) === 0) {
            return;
        }

        $diffWithFinalPrice = $roundedFinalPrice - $roundedTotalPayments;
        $diffForPayment = $diffWithFinalPrice / $quote->scheduleData->value->count();

        $quote->scheduleData->value->transform(function ($payment) use ($diffForPayment) {
            $price = data_get($payment, 'price', 0.0);
            data_set($payment, 'price', $price + $diffForPayment);

            return $payment;
        });
    }

    public function assignComputableRows(Quote $quote): void
    {
        $quote->computableRows = cache()->sear($quote->computableRowsCacheKey, function () use ($quote) {
            return $quote->getFlattenOrGroupedRows(['where_selected'], $quote->calculate_list_price);
        });

        if (!isset($quote->computableRows->first()->price)) {
            data_fill($quote->computableRows, '*.price', 0.0);
        }
    }

    public function prepareQuoteReview(Quote $quote): void
    {
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

    public function prepareQuoteExport(Quote $quote): array
    {
        $this->prepareQuoteReview($quote);

        $resource = (new QuoteResource($quote))->resolve();
        $data = json_decode(json_encode($resource['quote_data']), true);

        $design = $quote->quoteTemplate->form_values_data;

        if (isset($design['payment_page'])) {
            $design['payment_schedule'] = $design['payment_page'];
            unset($design['payment_page']);
        }

        $company_logos = $quote->quoteTemplate->company->getLogoDimensionsAttribute(true, true) ?? [];
        $vendor_logos = $quote->quoteTemplate->vendor->getLogoDimensionsAttribute(true, true) ?? [];
        $images = array_merge($company_logos, $vendor_logos);

        return compact('data', 'design', 'images');
    }

    public function modifyColumn(Quote $quote, string $column, Closure $callback): void
    {
        if (!isset($quote->computableRows)) {
            return;
        }

        $quote->computableRows->transform(function ($row) use ($column, $callback) {
            $row[$column] = $callback($row);
            return $row;
        });
    }

    public function export(Quote $quote)
    {
        $export = $this->prepareQuoteExport($quote);

        if (blank($export['design'])) {
            return;
        };

        $hash = md5($quote->customer->rfq . time());
        $filename = "{$quote->customer->rfq}_{$hash}.pdf";
        $original_file_path = "{$quote->user->quoteFilesDirectory}/$filename";
        $path = Storage::path($original_file_path);
        $this->pdfWrapper()->loadView('quotes.pdf', $export)->save(storage_path("app/{$original_file_path}"));

        $this->quoteFile->createPdf($quote, compact('original_file_path', 'filename'));
    }

    public function inlinePdf(Quote $quote, bool $html = false)
    {
        $export = array_merge($this->prepareQuoteExport($quote), compact('html'));

        if ($html) {
            return view('quotes.pdf', $export);
        }

        return $this->pdfWrapper()->loadView('quotes.pdf', $export)->inline();
    }

    public function prepareRows(Quote $quote): void
    {
        $keys = $quote->has_group_description && $quote->use_groups
            ? array_merge($quote->rowsHeaderToArray(), array_flip(['group_name']))
            : $quote->rowsHeaderToArray();

        $quote->computableRows->sortKeysByKeys($keys);

        $quote->computableRows = $quote->computableRows->exceptEach($quote->hiddenFieldsToArray());

        if ($quote->has_group_description && $quote->use_groups) {
            $groups_meta = $quote->getGroupDescriptionWithMeta(null, $quote->calculate_list_price);
            $quote->computableRows = $quote->computableRows
                ->rowsToGroups('group_name', $groups_meta, true, $quote->quoteTemplate->currency_symbol)
                ->exceptEach('group_name')
                ->sortByFields($quote->sort_group_description);

            return;
        }

        $this->modifyColumn($quote, 'price', function ($row) use ($quote) {
            return Str::prepend(Str::decimal($row['price']), $quote->quoteTemplate->currency_symbol, true);
        });
    }

    public function prepareSchedule(Quote $quote): void
    {
        if (!isset($quote->scheduleData->value)) {
            return;
        }

        $quote->scheduleData->value = collect($quote->scheduleData->value)
            ->transform(function ($payment) use ($quote) {
                $price = data_get($payment, 'price', 0.0);
                data_set($payment, 'price', Str::prepend(Str::decimal($price, 3), $quote->quoteTemplate->currency_symbol));

                return $payment;
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
}
