<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Rescue\Models\Contract;

interface ContractView
{
    public function prepareSchedule(Contract $contract);

    public function prepareRows(Contract $contract);

    public function export(Contract $contract);

    public function prepareContractReview(Contract $contract);

    public function setComputableRows(Contract $contract);
}
