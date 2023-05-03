<?php

namespace App\Domain\Discount\Contracts;

use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Requests\{UpdatePromotionalDiscountRequest};
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
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Promotional Discounts query.
     */
    public function userQuery(): Builder;

    /**
     * Find a specified Promotional Discount.
     */
    public function find(string $id): PromotionalDiscount;

    /**
     * Create Promotional Discount.
     *
     * @param \App\Domain\Discount\Requests\StorePromotionalDiscountRequest|array $request
     */
    public function create($request): PromotionalDiscount;

    /**
     * Update a specified Promotional Discount.
     *
     * @param \App\Domain\Discount\Requests\UpdatePromotionalDiscountRequest $request
     */
    public function update(UpdatePromotionalDiscountRequest $request, string $id): PromotionalDiscount;

    /**
     * Delete a specified Promotional Discount.
     */
    public function delete(string $id): bool;

    /**
     * Activate a specified Promotional Discount.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate a specified Promotional Discount.
     */
    public function deactivate(string $id): bool;
}
