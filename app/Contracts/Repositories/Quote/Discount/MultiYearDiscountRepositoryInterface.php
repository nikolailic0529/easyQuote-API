<?php

namespace App\Contracts\Repositories\Quote\Discount;

use App\Http\Requests\Discount\{
    StoreMultiYearDiscountRequest,
    UpdateMultiYearDiscountRequest
};
use App\Models\Quote\Discount\MultiYearDiscount;
use Illuminate\Database\Eloquent\Builder;

interface MultiYearDiscountRepositoryInterface
{
    /**
     * Get all MultiYear Discounts.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search over MultiYear Discounts.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * MultiYear Discounts query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find a specified MultiYear Discount.
     *
     * @param string $id
     * @return \App\Models\Quote\Discount\MultiYearDiscount
     */
    public function find(string $id): MultiYearDiscount;

    /**
     * Create MultiYear Discount.
     *
     * @param \App\Http\Requests\Discount\StoreMultiYearDiscountRequest|array $request
     * @return \App\Models\Quote\Discount\MultiYearDiscount
     */
    public function create($request): MultiYearDiscount;

    /**
     * Update a specified MultiYear Discount.
     *
     * @param \App\Http\Requests\Discount\UpdateMultiYearDiscountRequest $request
     * @param string $id
     * @return \App\Models\Quote\Discount\MultiYearDiscount
     */
    public function update(UpdateMultiYearDiscountRequest $request, string $id): MultiYearDiscount;

    /**
     * Delete a specified MultiYear Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate a specified MultiYear Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate a specified MultiYear Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
