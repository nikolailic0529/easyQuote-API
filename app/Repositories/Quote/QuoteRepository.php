<?php namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteRepositoryInterface;
use App\Models \ {
    Company,
    QuoteFile\DataSelectSeparator
};

class QuoteRepository implements QuoteRepositoryInterface
{
    public function step1()
    {
        $companies = Company::with('vendors.countries.languages')->get();
        $data_select_separators = DataSelectSeparator::all();

        return compact('data_select_separators', 'companies');
    }
}