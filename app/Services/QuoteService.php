<?php

namespace App\Services;

use App\Collections\MappedRows;
use App\Contracts\Services\QuoteServiceInterface;
use App\Http\Resources\QuoteResource;
use App\Models\{
    Quote\BaseQuote,
    Quote\Quote,
    Quote\Discount,
    Quote\QuoteVersion,
    Quote\Margin\CountryMargin,
    QuoteTemplate\BaseQuoteTemplate,
    User
};
use App\Notifications\{
    GrantedQuoteAccess,
    RevokedQuoteAccess,
};
use App\Repositories\Concerns\FetchesGroupDescription;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Arr, Str;

class QuoteService implements QuoteServiceInterface
{
    use FetchesGroupDescription;

    const QUOTE_EXPORT_VIEW = 'quotes.pdf';

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
                $row->price = $countryMargin->calculate($row->price);
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
            $row->price = round($row->price / $divider, 2);
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

            $value = $this->calculateDiscountValue($discount, (float) $row->computablePrice, (float) $quote->totalPrice);
            $quote->applicableDiscounts += $value;
            $row->computablePrice -= $value;

            return $row;
        });

        if (!$discount instanceof Discount) {
            return $this;
        }

        $listPriceAfterDiscount = $quote->totalPrice - $quote->applicableDiscounts;

        $discount->margin_percentage = (float) $listPriceAfterDiscount != 0
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

        $payments = $payments->map(fn ($payment) => data_set($payment, 'price', (float) data_get($payment, 'price')));

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
        $quote->computableRows =
            /** Set computable rows in the model. */
            cache()->sear($quote->computableRowsCacheKey, fn () => $quote->getMappedRows(
                fn (Builder $query) => $query->when(
                    $quote->groupsReady(),
                    /** When quote has groups and proposed to use groups we are retrieving rows with group_name. */
                    fn (Builder $query) => $query->whereIn('id', $quote->groupedRows()),
                    /** Otherwise we are retrieving only selected rows. */
                    fn (Builder $query) => $query->where('is_selected', true)
                )
            ));

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

    public function export(BaseQuote $quote)
    {
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

    public function handleQuoteGrantedUsers(Quote $quote, array $users)
    {
        $granted = Collection::wrap(Arr::get($users, 'granted'))->whereInstanceOf(User::class);
        $revoked = Collection::wrap(Arr::get($users, 'revoked'))->whereInstanceOf(User::class);

        $causer = auth()->user();
        $grantedMessage = sprintf(
            'User %s has granted you access to Quote RFQ %s',
            optional($causer)->email,
            optional($quote->customer)->rfq
        );
        $revokedMessage = sprintf(
            'User %s has revoked your access to Quote RFQ %s',
            optional($causer)->email,
            optional($quote->customer)->rfq
        );

        $granted->each(
            fn (User $user) =>
            tap(notification()
                ->for($user)
                ->message($grantedMessage)
                ->subject($user)
                ->url(ui_route('quotes.status', ['quote' => $quote]))
                ->priority(2)
                ->store(), fn () => $user->notify(new GrantedQuoteAccess($causer, $quote)))
        );

        $revoked->each(
            fn (User $user) =>
            tap(notification()
                ->for($user)
                ->message($revokedMessage)
                ->subject($user)
                ->priority(2)
                ->store(), fn () => $user->notify(new RevokedQuoteAccess($causer, $quote)))
        );

        return true;
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
        $hash = md5($quote->customer->rfq . time());

        return "{$quote->customer->rfq}_{$hash}.pdf";
    }
}
