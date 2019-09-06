<?php namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteRepositoryInterface;
use App\Models \ {
    Company,
    QuoteFile\DataSelectSeparator
};

class QuoteRepository implements QuoteRepositoryInterface
{
    protected $company;
    
    protected $dataSelectSeparator;

    public function __construct(Company $company, DataSelectSeparator $dataSelectSeparator)
    {
        $this->company = $company;
        $this->dataSelectSeparator = $dataSelectSeparator;
    }

    public function step1()
    {
        $companies = $this->company->with('vendors.countries.languages')->get();
        $data_select_separators = $this->dataSelectSeparator->all();

        return compact('data_select_separators', 'companies');
    }
}