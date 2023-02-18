<?php

namespace App\Domain\Margin\Contracts;

use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Margin\Requests\{UpdateCountryMarginRequest};
use App\Domain\Rescue\Models\BaseQuote as Quote;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

interface MarginRepositoryInterface
{
    /**
     * Get data for Creating New Quote.
     */
    public function data(): array;

    /**
     * Get Margins query for Authenticated User.
     */
    public function userQuery(): Builder;

    /**
     * Create Country Margin.
     *
     * @param \App\Domain\Margin\Requests\StoreCountryMarginRequest|array $request
     */
    public function create($request): CountryMargin;

    /**
     * Find Acceptable Country Margin or Create new.
     *
     * @param \App\Domain\Rescue\Models\Quote $quote
     */
    public function firstOrCreate(Quote $quote, array $attributes): CountryMargin;

    /**
     * Update Country Margin.
     *
     * @param \App\Domain\Margin\Requests\UpdateCountryMarginRequest $request
     */
    public function update(UpdateCountryMarginRequest $request, string $id): CountryMargin;

    /**
     * Get Country Margin by id.
     *
     * @return \App\Domain\Margin\Models\CountryMargin|NotFoundHttpException
     */
    public function find(string $id);

    /**
     * Retrieve random existing Margin.
     *
     * @param \Closure $scope
     *
     * @return \App\Domain\Margin\Models\CountryMargin|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1, ?\Closure $scope = null);

    /**
     * Delete specified Country Margin.
     *
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
     * @return mixed
     */
    public function search(string $search = '');

    /**
     * Activate Country Margin.
     *
     * @return void
     */
    public function activate(string $id);

    /**
     * Deactivate Country Margin.
     *
     * @return void
     */
    public function deactivate(string $id);
}
