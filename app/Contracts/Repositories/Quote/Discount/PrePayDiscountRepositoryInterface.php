<?php namespace App\Contracts\Repositories\Quote\Discount;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\Discount \ {
    StorePrePayDiscountRequest,
    UpdatePrePayDiscountRequest
};
use App\Models\Quote\Discount\PrePayDiscount;
use Illuminate\Database\Eloquent\Builder;

interface PrePayDiscountRepositoryInterface
{
    /**
     * Get all User's PrePay Discounts.
     *
     * @return Paginator
     */
    public function all(): Paginator;

    /**
     * Search over User's PrePay Discounts.
     *
     * @param string $query
     * @return Paginator
     */
    public function search(string $query = ''): Paginator;

    /**
     * User's PrePay Discounts query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find User's PrePay Discount.
     *
     * @param string $id
     * @return PrePayDiscount
     */
    public function find(string $id): PrePayDiscount;

    /**
     * Create User's PrePay Discount.
     *
     * @param UpdatePrePayDiscountRequest $request
     * @return PrePayDiscount
     */
    public function create(StorePrePayDiscountRequest $request): PrePayDiscount;

    /**
     * Update User's PrePay Discount.
     *
     * @param UpdatePrePayDiscountRequest $request
     * @param string $id
     * @return PrePayDiscount
     */
    public function update(UpdatePrePayDiscountRequest $request, string $id): PrePayDiscount;

    /**
     * Delete User's PrePay Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate User's PrePay Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate User's PrePay Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
