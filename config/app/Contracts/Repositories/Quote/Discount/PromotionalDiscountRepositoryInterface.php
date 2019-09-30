<?php namespace App\Contracts\Repositories\Quote\Discount;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\Discount \ {
    StorePromotionalDiscountRequest,
    UpdatePromotionalDiscountRequest
};
use App\Models\Quote\Discount\PromotionalDiscount;
use Illuminate\Database\Eloquent\Builder;

interface PromotionalDiscountRepositoryInterface
{
    /**
     * Get all User's Promotional Discounts.
     *
     * @return Paginator
     */
    public function all(): Paginator;

    /**
     * Search over User's Promotional Discounts.
     *
     * @param string $query
     * @return Paginator
     */
    public function search(string $query = ''): Paginator;

    /**
     * User's Promotional Discounts query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find User's Promotional Discount.
     *
     * @param string $id
     * @return PromotionalDiscount
     */
    public function find(string $id): PromotionalDiscount;

    /**
     * Create User's Promotional Discount.
     *
     * @param StorePromotionalDiscountRequest $request
     * @return PromotionalDiscount
     */
    public function create(StorePromotionalDiscountRequest $request): PromotionalDiscount;

    /**
     * Update User's Promotional Discount.
     *
     * @param UpdatePromotionalDiscountRequest $request
     * @param string $id
     * @return PromotionalDiscount
     */
    public function update(UpdatePromotionalDiscountRequest $request, string $id): PromotionalDiscount;

    /**
     * Delete User's Promotional Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate User's Promotional Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate User's Promotional Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
