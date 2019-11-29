<?php

namespace App\Contracts\Repositories\Quote\Discount;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\Discount\{
    StoreMultiYearDiscountRequest,
    UpdateMultiYearDiscountRequest
};
use App\Models\Quote\Discount\MultiYearDiscount;
use Illuminate\Database\Eloquent\Builder;

interface MultiYearDiscountRepositoryInterface
{
    /**
     * Get all User's MultiYear Discounts.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search over User's MultiYear Discounts.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * User's MultiYear Discounts query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find User's MultiYear Discount.
     *
     * @param string $id
     * @return MultiYearDiscount
     */
    public function find(string $id): MultiYearDiscount;

    /**
     * Create User's MultiYear Discount.
     *
     * @param StoreMultiYearDiscountRequest $request
     * @return MultiYearDiscount
     */
    public function create(StoreMultiYearDiscountRequest $request): MultiYearDiscount;

    /**
     * Update User's MultiYear Discount.
     *
     * @param UpdateMultiYearDiscountRequest $request
     * @param string $id
     * @return MultiYearDiscount
     */
    public function update(UpdateMultiYearDiscountRequest $request, string $id): MultiYearDiscount;

    /**
     * Delete User's MultiYear Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate User's MultiYear Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate User's MultiYear Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
