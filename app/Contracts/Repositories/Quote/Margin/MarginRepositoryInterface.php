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
     * Get Margins User's query for Authenticated User
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Create Country Margin
     *
     * @param StoreCountryMarginRequest $request
     * @return CountryMargin
     */
    public function create(StoreCountryMarginRequest $request): CountryMargin;

    /**
     * Update User's Country Margin
     *
     * @param UpdateCountryMarginRequest $request
     * @param string $id
     * @return CountryMargin
     */
    public function update(UpdateCountryMarginRequest $request, string $id): CountryMargin;

    /**
     * Get User's Country Margin by id
     *
     * @param string $id
     * @return CountryMargin|NotFoundHttpException
     */
    public function find(string $id);

    /**
     * Delete specified User's Country Margin
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id);

    /**
     * Get All User's Country Margins
     *
     * @return Collection
     */
    public function all(): Paginator;

    /**
     * Activate User's Country Margin.
     *
     * @param string $id
     * @return void
     */
    public function activate(string $id);

    /**
     * Deactivate User's Country Margin.
     *
     * @param string $id
     * @return void
     */
    public function deactivate(string $id);
}
