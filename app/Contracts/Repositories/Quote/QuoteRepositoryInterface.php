<?php namespace App\Contracts\Repositories\Quote;

interface QuoteRepositoryInterface
{
    /**
     * Return linked data Company->Vendor->Country->Language
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function step1();
}