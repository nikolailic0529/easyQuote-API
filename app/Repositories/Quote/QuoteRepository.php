<?php namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteRepositoryInterface;
use App\Models\Company;

class QuoteRepository implements QuoteRepositoryInterface
{
    public function step1()
    {
        return Company::with('vendors.countries.languages')->get();
    }
}