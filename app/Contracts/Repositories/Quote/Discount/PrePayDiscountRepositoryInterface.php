<?php

namespace App\Contracts\Repositories\Quote\Discount;

use App\Http\Requests\Discount\{
    StorePrePayDiscountRequest,
    UpdatePrePayDiscountRequest
};
use App\Models\Quote\Discount\PrePayDiscount;
use Illuminate\Database\Eloquent\Builder;

interface PrePayDiscountRepositoryInterface
{
    /**
     * Get all PrePay Discounts.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search over PrePay Discounts.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * PrePay Discounts Query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find a specified PrePay Discount.
     *
     * @param string $id
     * @return \App\Models\Quote\Discount\PrePayDiscount
     */
    public function find(string $id): PrePayDiscount;

    /**
     * Create PrePay Discount.
     *
     * @param \App\Http\Requests\Discount\StorePrePayDiscountRequest|array $request
     * @return \App\Models\Quote\Discount\PrePayDiscount
     */
    public function create($request): PrePayDiscount;

    /**
     * Update a specified PrePay Discount.
     *
     * @param \App\Http\Requests\Discount\UpdatePrePayDiscountRequest $request
     * @param string $id
     * @return \App\Models\Quote\Discount\PrePayDiscount
     */
    public function update(UpdatePrePayDiscountRequest $request, string $id): PrePayDiscount;

    /**
     * Delete a specified PrePay Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate a specified PrePay Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate a specified PrePay Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
