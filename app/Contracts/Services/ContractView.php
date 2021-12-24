<?php

namespace App\Contracts\Services;

use App\Models\Quote\Contract;

interface ContractView
{
    public function prepareSchedule(Contract $contract);

    public function prepareRows(Contract $contract);

    public function export(Contract $contract);

    public function prepareContractReview(Contract $contract);

    public function setComputableRows(Contract $contract);
}