<?php

namespace App\Contracts\Repositories\Quote\Margin;

use App\Http\Requests\Margin\{
    StoreCountryMarginRequest,
    UpdateCountryMarginRequest
};
use App\Models\Quote\{
    BaseQuote as Quote,
    Margin\CountryMargin
};
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Closure;

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
     * @param \App\Http\Requests\Margin\StoreCountryMarginRequest|array $request
     * @return \App\Models\Quote\Margin\CountryMargin
     */
    public function create($request): CountryMargin;

    /**
     * Find Acceptable Country Margin or Create new.
     *
     * @param \App\Models\Quote\Quote $quote
     * @param array $attributes
     * @return \App\Models\Quote\Margin\CountryMargin
     */
    public function firstOrCreate(Quote $quote, array $attributes): CountryMargin;

    /**
     * Update Country Margin.
     *
     * @param \App\Http\Requests\Margin\UpdateCountryMarginRequest $request
     * @param string $id
     * @return \App\Models\Quote\Margin\CountryMargin
     */
    public function update(UpdateCountryMarginRequest $request, string $id): CountryMargin;

    /**
     * Get Country Margin by id.
     *
     * @param string $id
     * @return \App\Models\Quote\Margin\CountryMargin|NotFoundHttpException
     */
    public function find(string $id);

    /**
     * Retrieve random existing Margin.
     *
     * @param int $limit
     * @param Closure $scope
     * @return \App\Models\Quote\Margin\CountryMargin|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1, ?Closure $scope = null);

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
