<?php

namespace App\Events\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CompanyDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(private Company $company,
                                private ?Model $causer = null)
    {
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }
}
