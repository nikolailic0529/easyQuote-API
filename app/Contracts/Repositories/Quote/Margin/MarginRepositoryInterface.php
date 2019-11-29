<?php

namespace App\Contracts\Repositories\Quote\Margin;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\Margin\{
    StoreCountryMarginRequest,
    UpdateCountryMarginRequest
};
use App\Models\Quote\{
    Quote,
    Margin\CountryMargin
};
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

interface MarginRepositoryInterface
{
    /**
     * Get data for Creating New Quote.
     *
     * @return array
     */
    public function data(): array;

    /**
     * Get Margins query for Authenticated User.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Create Country Margin.
     *
     * @param StoreCountryMarginRequest $request
     * @return CountryMargin
     */
    public function create(StoreCountryMarginRequest $request): CountryMargin;

    /**
     * Find Acceptable Country Margin or Create new.
     *
     * @param Quote $quote
     * @param array $attributes
     * @return CountryMargin
     */
    public function firstOrCreate(Quote $quote, array $attributes): CountryMargin;

    /**
     * Update Country Margin.
     *
     * @param UpdateCountryMarginRequest $request
     * @param string $id
     * @return CountryMargin
     */
    public function update(UpdateCountryMarginRequest $request, string $id): CountryMargin;

    /**
     * Get Country Margin by id.
     *
     * @param string $id
     * @return CountryMargin|NotFoundHttpException
     */
    public function find(string $id);

    /**
     * Delete specified Country Margin.
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id);

    /**
     * Get All Country Margins.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search through Margins.
     *
     * @param string $search
     * @return mixed
     */
    public function search(string $search = '');

    /**
     * Activate Country Margin.
     *
     * @param string $id
     * @return void
     */
    public function activate(string $id);

    /**
     * Deactivate Country Margin.
     *
     * @param string $id
     * @return void
     */
    public function deactivate(string $id);
}
