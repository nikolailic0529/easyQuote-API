<?php

namespace App\Domain\Discount\Contracts;

use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Requests\{UpdatePrePayDiscountRequest};
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
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * PrePay Discounts Query.
     */
    public function userQuery(): Builder;

    /**
     * Find a specified PrePay Discount.
     */
    public function find(string $id): PrePayDiscount;

    /**
     * Create PrePay Discount.
     *
     * @param \App\Domain\Discount\Requests\StorePrePayDiscountRequest|array $request
     */
    public function create($request): PrePayDiscount;

    /**
     * Update a specified PrePay Discount.
     *
     * @param \App\Domain\Discount\Requests\UpdatePrePayDiscountRequest $request
     */
    public function update(UpdatePrePayDiscountRequest $request, string $id): PrePayDiscount;

    /**
     * Delete a specified PrePay Discount.
     */
    public function delete(string $id): bool;

    /**
     * Activate a specified PrePay Discount.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate a specified PrePay Discount.
     */
    public function deactivate(string $id): bool;
}
