<?php

namespace App\Contracts\Repositories\Quote;

use App\Models\Quote\{
    Contract,
    Quote
};

interface ContractStateRepositoryInterface
{
    /**
     * Make new Contract instance with the given attributes.
     *
     * @param array $attributes
     * @return \App\Models\Quote\Contract
     */
    public function make(array $attributes = []): Contract;

    /**
     * Find the specified Contract by id.
     *
     * @param string $id
     * @return \App\Models\Quote\Contract|null
     */
    public function find(string $id);

    /**
     * Undocumented function
     *
     * @param array $state
     * @param \App\Models\Quote\Contract|string $contract
     * @return mixed
     */
    public function storeState(array $state, $contract);

    /**
     * Create a new Contract from the given Quote.
     *
     * @param \App\Models\Quote\Quote $quote
     * @param array $attributes
     * @return mixed
     */
    public function createFromQuote(Quote $quote, array $attributes = []);
}
