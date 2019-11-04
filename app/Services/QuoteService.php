<?php namespace App\Services;

use App\Contracts \ {
    Services\QuoteServiceInterface,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository,
    Repositories\QuoteFile\FileFormatRepositoryInterface as FileFormatRepository
};
use App\Http\Resources\QuoteResource;
use App\Models\Quote \ {
    Quote,
    Discount,
    Margin\CountryMargin
};
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Storage, Closure, Str, Arr, File;

class QuoteService implements QuoteServiceInterface
{
    /**
     * DomPDF
     *
     * @var Barryvdh\Snappy\PdfWrapper
     */
    protected $pdf;

    protected $quoteFile;

    protected $fileFormat;

    public function __construct(QuoteFileRepository $quoteFile, FileFormatRepository $fileFormat)
    {
        $this->pdf = app('snappy.pdf.wrapper');
        $this->quoteFile = $quoteFile;
        $this->fileFormat = $fileFormat;
    }

    public function interact(Quote $quote, $interactable): Quote
    {
        if($interactable instanceof CountryMargin) {
            return $this->interactWithCountryMargin($quote, $interactable);
        }
        if($interactable instanceof Discount || is_numeric($interactable)) {
            return $this->interactWithDiscount($quote, $interactable);
        }
        if(is_iterable($interactable)) {
            collect($interactable)->each(function ($entity) use ($quote) {
                $this->interact($quote, $entity);
            });
        }

        return $quote;
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
        $this->interact($quote, collect($quote->discounts)->prepend($quote->custom_discount));
    }

    public function interactWithCountryMargin(Quote $quote, CountryMargin $countryMargin): Quote
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        if($countryMargin->isPercentage() && $countryMargin->isNoMargin()) {
            $quote->computableRows->transform(function ($row) use ($countryMargin) {
                $row->price = $countryMargin->calculate($row->price);
                return $row;
            });
        }

        $list_price = $quote->countTotalPrice();

        if($countryMargin->isFixed() && $countryMargin->isNoMargin()) {
            $list_price = $countryMargin->calculate($list_price);
        }

        $quote->list_price = $list_price;

        return $quote;
    }

    public function interactWithMargin(Quote $quote): Quote
    {
        if(!isset($quote->computableRows) || !isset($quote->countryMargin)) {
            return $quote;
        }

        $divider = (100 - $quote->countryMargin->value) / 100;

        if((float) $divider === 0.0) {
            data_fill($quote->computableRows, '*.price', 0.0);

            $quote->list_price = 0.0;
            return $quote;
        }

        $quote->list_price = 0.0;

        $quote->computableRows->transform(function ($row) use ($divider, $quote) {
            $row->price = round($row->price / $divider, 2);
            $quote->list_price += $row->price;
            return $row;
        });

        return $quote;
    }

    public function interactWithDiscount(Quote $quote, $discount): Quote
    {
        if(!isset($quote->computableRows) || $discount === 0.0) {
            return $quote;
        }

        if(!isset($quote->list_price) || (float) $quote->list_price === 0.0) {
            $quote->list_price = $quote->countTotalPrice();
        }

        $quote->computableRows->transform(function ($row) use ($discount, $quote) {
            if(!isset($row->computablePrice)) {
                $row->computablePrice = $row->price;
            }

            $value = $this->calculateDiscountValue($discount, (float) $row->computablePrice, (float) $quote->list_price);
            $quote->applicable_discounts += $value;
            $row->computablePrice -= $value;

            return $row;
        });

        return $quote;
    }

    public function calculateSchedulePrices(Quote $quote): Quote
    {
        if(!isset($quote->scheduleData->value) || !isset($quote->countryMargin)) {
            return $quote;
        }

        $divider = (100 - $quote->countryMargin->value) / 100;

        if((float) $divider === 0.0) {
            data_fill($quote->scheduleData->value, '*.price', 0.0);

            return $quote;
        }

        $quote->scheduleData->value = collect($quote->scheduleData->value)->map(function ($payment) use ($divider) {
            $payment['price'] = round(Str::price($payment['price']) / $divider, 2);
            return $payment;
        });

        return $quote;
    }

    public function prepareQuoteReview(Quote $quote): void
    {
        $quote->computableRows = $quote->rowsDataByColumnsCalculated(['where_selected'])->get();

        if(!isset($quote->computableRows->first()->price)) {
            data_fill($quote->computableRows, '*.price', 0.0);
        }

        /**
         * Possible Interactions with Margins and Discounts
         */
        $this->interactWithModels($quote);

        /**
         * Calculate List Price if not calculated after interactions
         */
        if((float) $quote->list_price === 0.0) {
            $quote->list_price = $quote->countTotalPrice();
        }

        /**
         * Calculate Schedule Total Prices based on Margin Percentage
         */
        $this->calculateSchedulePrices($quote);

        $this->prepareRows($quote);
    }

    public function modifyColumn(Quote $quote, string $column, Closure $callback): void
    {
        if(!isset($quote->computableRows)) {
            return;
        }

        $quote->computableRows->transform(function ($row) use ($column, $callback) {
            $row->{$column} = $callback($row);
            return $row;
        });
    }

    public function export(Quote $quote)
    {
        $this->prepareQuoteReview($quote);

        $resource = (new QuoteResource($quote))->resolve();
        $data = $resource['quote_data'];
        $design = $quote->quoteTemplate->form_values_data;

        if(isset($design['payment_page'])) {
            $design['payment_schedule'] = $design['payment_page'];
            unset($design['payment_page']);
        }

        $company_logos = $quote->quoteTemplate->company->getLogoDimensionsAttribute(true, true) ?? [];
        $vendor_logos = $quote->quoteTemplate->vendor->getLogoDimensionsAttribute(true, true) ?? [];
        $images = array_merge($company_logos, $vendor_logos);

        $hash = md5($quote->customer->rfq . time());
        $filename = "{$quote->customer->rfq}_{$hash}.pdf";
        $original_file_path = "{$quote->user->quoteFilesDirectory}/$filename";
        $path = storage_path("app/{$original_file_path}");
        $this->pdf->loadView('quotes.pdf', compact('data', 'design', 'images'))->save($path);

        $quote_file = new UploadedFile($path, $filename);
        $format = $this->fileFormat->whereInExtension(['pdf']);
        $quote_id = $quote->id;
        $file_type = 'Generated PDF';

        $quote->generatedPdf()->delete();
        $this->quoteFile->create(compact('quote_file', 'format', 'file_type', 'original_file_path', 'quote_id'));
    }

    public function prepareRows(Quote $quote): void
    {
        $quote->computableRows->sortKeysByKeys($quote->rowsHeaderToArray());

        // $this->modifyColumn($quote, 'buy_price', function ($row) use ($quote) {
        //     return round($row->price - ($row->price * ($quote->margin_percentage / 100)), 2);
        // });

        if(isset(head($quote->computableRows)->price)) {
            $this->modifyColumn($quote, 'price', function ($row) {
                return round($row->price, 2);
            });
        }
    }

    private function calculateDiscountValue($discount, float $price, float $list_price): float
    {
        if($discount instanceof Discount) {
            return $discount->calculateDiscount($price, $list_price);
        }

        if(is_numeric($discount)) {
            return $price * $discount / 100;
        }

        return 0.0;
    }
}
