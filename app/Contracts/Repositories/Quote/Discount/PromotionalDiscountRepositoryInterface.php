<?php

namespace App\Contracts\Repositories\Quote\Discount;

use App\Http\Requests\Discount\{
    StorePromotionalDiscountRequest,
    UpdatePromotionalDiscountRequest
};
use App\Models\Quote\Discount\PromotionalDiscount;
use Illuminate\Database\Eloquent\Builder;

interface PromotionalDiscountRepositoryInterface
{
    /**
     * Get all Promotional Discounts.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search over Promotional Discounts.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Promotional Discounts query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find a specified Promotional Discount.
     *
     * @param string $id
     * @return \App\Models\Quote\Discount\PromotionalDiscount
     */
    public function find(string $id): PromotionalDiscount;

    /**
     * Create Promotional Discount.
     *
     * @param \App\Http\Requests\Discount\StorePromotionalDiscountRequest|array $request
     * @return \App\Models\Quote\Discount\PromotionalDiscount
     */
    public function create($request): PromotionalDiscount;

    /**
     * Update a specified Promotional Discount.
     *
     * @param \App\Http\Requests\Discount\UpdatePromotionalDiscountRequest $request
     * @param string $id
     * @return \App\Models\Quote\Discount\PromotionalDiscount
     */
    public function update(UpdatePromotionalDiscountRequest $request, string $id): PromotionalDiscount;

    /**
     * Delete a specified Promotional Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function delete(string $id): bool;

    /**
     * Activate a specified Promotional Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function activate(string $id): bool;

    /**
     * Deactivate a specified Promotional Discount.
     *
     * @param string $id
     * @return boolean
     */
    public function deactivate(string $id): bool;
}
