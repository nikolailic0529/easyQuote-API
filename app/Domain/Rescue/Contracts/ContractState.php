<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Models\{
    Quote
};

interface ContractState
{
    /**
     * Make new Contract instance with the given attributes.
     */
    public function make(array $attributes = []): Contract;

    /**
     * Find the specified Contract by id.
     *
     * @return \App\Domain\Rescue\Models\Contract|null
     */
    public function find(string $id);

    /**
     * Undocumented function.
     *
     * @param \App\Domain\Rescue\Models\Contract|string $contract
     *
     * @return mixed
     */
    public function storeState(array $state, $contract);

    /**
     * Create a new Contract from the given Quote.
     *
     * @param \App\Domain\Rescue\Models\Quote $quote
     *
     * @return mixed
     */
    public function createFromQuote(Quote $quote, array $attributes = []);
}
