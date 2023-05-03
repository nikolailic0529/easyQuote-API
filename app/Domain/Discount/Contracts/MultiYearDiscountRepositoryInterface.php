<?php

namespace App\Domain\Discount\Contracts;

use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Requests\{UpdateMultiYearDiscountRequest};
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
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * MultiYear Discounts query.
     */
    public function userQuery(): Builder;

    /**
     * Find a specified MultiYear Discount.
     */
    public function find(string $id): MultiYearDiscount;

    /**
     * Create MultiYear Discount.
     *
     * @param \App\Domain\Discount\Requests\StoreMultiYearDiscountRequest|array $request
     */
    public function create($request): MultiYearDiscount;

    /**
     * Update a specified MultiYear Discount.
     *
     * @param \App\Domain\Discount\Requests\UpdateMultiYearDiscountRequest $request
     */
    public function update(UpdateMultiYearDiscountRequest $request, string $id): MultiYearDiscount;

    /**
     * Delete a specified MultiYear Discount.
     */
    public function delete(string $id): bool;

    /**
     * Activate a specified MultiYear Discount.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate a specified MultiYear Discount.
     */
    public function deactivate(string $id): bool;
}
