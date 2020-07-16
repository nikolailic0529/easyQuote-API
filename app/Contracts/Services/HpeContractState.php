<?php

namespace App\Contracts\Services;

use App\Models\HpeContract;

interface HpeContractState
{
    /**
     * Process HPE Contract state.
     * If the HPE Contract is present as argument, we will asume process state for the given instance.
     * Otherwise new HPE contract will be created.
     *
     * @param array $state
     * @param HpeContract|null $hpeContract
     * @return void
     */
    public function processState(array $state, ?HpeContract $hpeContract = null);
}