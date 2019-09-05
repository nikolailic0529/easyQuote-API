<?php namespace App\Contracts\Repositories\QuoteTemplate;

interface TemplateFieldRepositoryInterface
{
    /**
     * Get all Template Fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();
}