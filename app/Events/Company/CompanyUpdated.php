<?php

namespace App\Events\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CompanyUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(private Company $company,
                                private Company $oldCompany,
                                private ?Model  $causer = null)
    {
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getOldCompany(): Company
    {
        return $this->oldCompany;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }
}
