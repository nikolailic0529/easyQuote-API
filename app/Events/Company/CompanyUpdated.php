<?php

namespace App\Events\Company;

use App\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CompanyUpdated
{
    use Dispatchable, SerializesModels;

    private Company $company;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Company $company
     * @return void
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }
}
