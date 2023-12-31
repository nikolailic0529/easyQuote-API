<?php

namespace App\Domain\Rescue\Services;

use App\Domain\Company\Models\Company;
use App\Domain\DocumentMapping\Collections\MappedRows;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Rescue\Concerns\FetchesGroupDescription;
use App\Domain\Rescue\Events\Quote\RescueQuoteExported;
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Rescue\Models\Discount;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Rescue\Models\QuoteVersion;
use App\Domain\Rescue\Queries\QuoteQueries;
use App\Domain\Rescue\Resources\V1\QuoteResource;
use App\Domain\Vendor\Models\Vendor;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuoteViewService implements \App\Domain\Rescue\Contracts\QuoteView
{
    use FetchesGroupDescription;
    const QUOTE_EXPORT_VIEW = 'quotes.pdf';

    protected ?Model $causer = null;

    public function __construct(protected QuoteQueries $quoteQueries,
                                protected RescueQuoteCalc $rescueQuoteCalc,
                                protected Dispatcher $eventDispatcher)
    {
    }

    public function requestForQuote(string $rfqNumber): BaseQuote
    {
        try {
            /** @var Quote $quote */
            $quote = $this->quoteQueries
                ->quoteByRfqNumberQuery($rfqNumber)
                ->sole();
        } catch (ModelNotFoundException) {
            error_abort(EQ_NF_01, 'EQ_NF_01', 422);
        }

        return tap($quote->activeVersionOrCurrent, function (BaseQuote $version) use ($quote) {
            $this->prepareQuoteReview($version);

            activity()
                ->on($quote)
                ->by($this->causer)
                ->queue('retrieved');
        });
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
            Collection::wrap($interactable)->each(fn ($entity) => $this->interact($quote, $entity));
        }
    }

    public function interactWithModels(BaseQuote $quote)
    {
        $quote->applicableDiscounts = 0;

        /*
         * Possible interaction with Margin percentage.
         */
        $this->interactWithMargin($quote);

        /*
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
        $countryMarginValue = (float) $quote->countryMargin?->value;
        $customDiscountValue = (float) $quote->custom_discount;

        $quote->totalPriceAfterMargin = self::calculateTotalPriceAfterBottomUp(
            totalPrice: $quote->totalPrice,
            buyPrice: (float) $quote->buy_price,
            marginDiffValue: $countryMarginValue
        );

        $quote->finalTotalPrice = self::calculateTotalPriceAfterBottomUp(
            totalPrice: $quote->totalPrice,
            buyPrice: (float) $quote->buy_price,
            marginDiffValue: $countryMarginValue - $customDiscountValue
        );

        $quote->applicableDiscounts = $quote->totalPriceAfterMargin - $quote->finalTotalPrice;
    }

    final public static function calculateTotalPriceAfterBottomUp(float $totalPrice,
                                                                  float $buyPrice,
                                                                  float $marginDiffValue): float
    {
        if ($totalPrice === 0.0) {
            return 0.0;
        }

        $initialMarginPercentage = (($totalPrice - $buyPrice) / $totalPrice) * 100;

        $marginFloat = ($initialMarginPercentage + $marginDiffValue) / 100;

        $marginDivider = $marginFloat >= 1
            ? 1 / ($marginFloat + 1)
            : 1 - $marginFloat;

        return $buyPrice / $marginDivider;
    }

    public function getMarginDivider(BaseQuote $quote): float
    {
        $countryMarginValue = (float) $quote->countryMargin?->value;

        $marginValue = ($countryMarginValue - (float) $quote->custom_discount) / 100;

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

        return (($totalPrice - (float) $buyPrice) / $totalPrice) * 100;
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

    private static function parsePriceValue(string $value): float
    {
        $cleanString = preg_replace('/([^0-9.,])/i', '', $value);
        $onlyNumbersString = preg_replace('/([^0-9])/i', '', $value);

        $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

        $stringWithCommaOrDot = preg_replace('/([,.])/', '', $cleanString, $separatorsCountToBeErased);
        $removedThousandSeparator = preg_replace('/([.,])(?=[0-9]{3,}$)/', '', $stringWithCommaOrDot);

        return (float) str_replace(',', '.', $removedThousandSeparator);
    }

    public function setComputableRows(BaseQuote $quote): void
    {
        $rows = $this->quoteQueries
            ->mappedSelectedRowsQuery($quote, true)
            ->get()
            ->map(static function (mixed $row) use ($quote): mixed {
                return data_set($row, 'country_iso_3166_2', $quote->customer?->country?->iso_3166_2);
            });

        $quote->computableRows = MappedRows::make($rows);
    }

    public function prepareQuoteReview(BaseQuote $quote): static
    {
        $totalRowsPrice = $this->rescueQuoteCalc->calculateListPriceOfRescueQuote($quote);

        $quote->totalPrice = $totalRowsPrice * (float) $quote->target_exchange_rate;
        $quote->buy_price = (float) $quote->buy_price * $quote->target_exchange_rate;

        $this->setComputableRows($quote);

        $this->interactWithMargin($quote);

        $this->applyPredefinedDiscounts($quote);

        $quote->priceCoef = match (true) {
            $quote->totalPrice > 0.0 => $quote->totalPriceAfterMargin / $totalRowsPrice,
            default => 0.0,
        };

        $this->prepareRows($quote);

        $this->prepareSchedule($quote);

        return $this;
    }

    public function export(BaseQuote $quote, int $type = QT_TYPE_QUOTE): Response
    {
        $quote->switchModeTo($type);

        $export = $this->prepareQuoteExport($quote);

        $filename = $this->makePdfFilename($quote);

        return tap(app('snappy.pdf.wrapper')
            ->loadView(self::QUOTE_EXPORT_VIEW, $export)
            ->download($filename), function () use ($type, $quote) {
                $this->eventDispatcher->dispatch(new RescueQuoteExported($quote, $type));
            });
    }

    public function buildView(BaseQuote $quote, int $type = QT_TYPE_QUOTE): View
    {
        $quote->switchModeTo($type);

        $export = $this->prepareQuoteExport($quote);

        return view(self::QUOTE_EXPORT_VIEW, $export, ['html' => true]);
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

        $paymentSchedule = $quote->scheduleData->value = Collection::wrap($quote->scheduleData->value)->map(function (array $payment) {
            $price = $payment['price'] ?? 0.0;

            $payment['price'] = is_float($price) ? $price : static::parsePriceValue($price);

            return $payment;
        });

        $totalPaymentsValue = (float) $paymentSchedule->sum('price');

        if ($totalPaymentsValue > 0.0) {
            $paymentCoef = ($quote->totalPriceAfterMargin / $totalPaymentsValue);
            // if custom discount is applied, then calculate paymentCoef with final value
            $customDiscountValue = (float) $quote->custom_discount;
            if ($customDiscountValue > 0.0) {
                $paymentCoef = ($quote->finalTotalPrice / $totalPaymentsValue);
            }
        } else {
            $paymentCoef = 0.0;
        }

        $quote->scheduleData->value = Collection::wrap($quote->scheduleData->value)->map(function (array $payment) use ($quote, $paymentCoef) {
            $newPaymentPrice = $payment['price'] * $paymentCoef;

            $payment['price'] = sprintf('%s %s', $quote->currencySymbol, number_format($newPaymentPrice, 2));

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
                ThumbHelper::ABS_PATH | ThumbHelper::MAP
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
                    ThumbHelper::ABS_PATH | ThumbHelper::MAP
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
                    ThumbHelper::ABS_PATH | ThumbHelper::MAP
                );
            })->collapse()->all();

            $vendorLogoForBackCompatibility = transform($template->vendors->first(), function (Vendor $vendor) {
                return ThumbHelper::getLogoDimensionsFromImage(
                    $vendor->image,
                    $vendor->thumbnailProperties(),
                    Str::snake(class_basename($vendor)),
                    ThumbHelper::ABS_PATH | ThumbHelper::MAP
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

        $quote->renderableRows = (clone $quote->computableRows)->exceptHeaders($quote->systemHiddenFields);

        if ($quote->isMode(QT_TYPE_CONTRACT)) {
            $quote->renderableRows = $quote->renderableRows->map->except('total_price');
            $quote->renderableRows->setHeadersCount();
        }

        return $this;
    }

    protected function formatLinePrices(BaseQuote $quote)
    {
        $quote->renderableRows = $quote->renderableRows->setCurrency($quote->currencySymbol);

        return $this;
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

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}
