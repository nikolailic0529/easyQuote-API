<?php namespace App\Contracts\Repositories\Quote\Margin;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\Margin \ {
    GetPercentagesCountryMarginsRequest,
    StoreCountryMarginRequest,
    UpdateCountryMarginRequest
};
use App\Models\Quote\Margin\CountryMargin;
use Illuminate\Database\Eloquent \ {
    Builder,
    Collection
};
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
     * Get available percentage values Country Margins by Quote Type, Margin method, Margin type.
     *
     * @param string $quoteType
     * @param string $method
     * @param string $type
     * @return array
     */
    public function percentages(GetPercentagesCountryMarginsRequest $request): CountryMargin;

    /**
     * Get Margins Country query for Authenticated User
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userCountryMarginsQuery(): Builder;

    /**
     * Create Country Margin
     *
     * @param StoreCountryMarginRequest $request
     * @return CountryMargin
     */
    public function createCountryMargin(StoreCountryMarginRequest $request): CountryMargin;

    /**
     * Update User's Country Margin
     *
     * @param UpdateCountryMarginRequest $request
     * @return CountryMargin
     */
    public function updateCountryMargin(UpdateCountryMarginRequest $request): CountryMargin;

    /**
     * Get User's Country Margin by id
     *
     * @param string $id
     * @return CountryMargin|NotFoundHttpException
     */
    public function getCountryMargin(string $id);

    /**
     * Delete specified User's Country Margin
     *
     * @param string $id
     * @return void
     */
    public function deleteCountryMargin(string $id);

    /**
     * Get All User's Country Margins
     *
     * @return Collection
     */
    public function allCountryMargins(): Paginator;

    /**
     * Search by All User's Country Margins
     *
     * @return Collection
     */
    public function searchCountryMargins(string $query): Paginator;

    /**
     * Deactivate User's Country Margin.
     *
     * @param string $id
     * @return void
     */
    public function deactivateCountryMargin(string $id);

    /**
     * Activate User's Country Margin.
     *
     * @param string $id
     * @return void
     */
    public function activateCountryMargin(string $id);
}
