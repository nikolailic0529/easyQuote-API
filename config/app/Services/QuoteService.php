<?php namespace App\Services;

use App\Contracts\Services\QuoteServiceInterface;
use App\Models\Quote \ {
    Quote,
    Margin\CountryMargin
};
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class QuoteService implements QuoteServiceInterface
{
    public function interact(Quote $quote, $model)
    {
        if($model instanceof CountryMargin) {
            return $this->interactWithCountryMargin($quote, $model);
        }

        return $quote;
    }

    public function interactWithCountryMargin(Quote $quote, CountryMargin $countryMargin)
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        $quote->computableRows->transform(function ($row) use ($countryMargin) {
            $dateFromColumn = $this->getRowColumn($row->columnsData, 'date_from');
            $dateToColumn = $this->getRowColumn($row->columnsData, 'date_to');
            $priceColumn = $this->getRowColumn($row->columnsData, 'price');

            $priceColumn->computed_value = $countryMargin->calculate($priceColumn->value, $dateFromColumn->value, $dateToColumn->value);

            return $row;
        });

        $quote->append([
            'total' => 1
        ]);

        return $quote;
    }

    private function getRowColumn(EloquentCollection $collection, string $name)
    {
        $importableColumns = $this->importableColumnsByName;

        if(!$importableColumns->has($name)) {
            return null;
        }

        return $collection->where('importable_column_id', $importableColumns->get($name))->first();
    }
}
