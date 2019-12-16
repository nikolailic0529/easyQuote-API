<?php

namespace App\Observers;

use App\Models\Company;

class CompanyObserver
{
    /**
     * Handle the Company "saving" event.
     *
     * @param Company $company
     * @return void
     */
    public function saving(Company $company)
    {
        if (app()->runningInConsole()) {
            return;
        }

        error_abort_if($this->exists($company), 'CPE_01', 409);
    }

    /**
     * Handle the Company "deleting" event.
     *
     * @param Company $company
     * @return void
     */
    public function deleting(Company $company)
    {
        if (app()->runningInConsole()) {
            return;
        }

        error_abort_if($company->inUse(), 'CPUD_01', 409);
    }

    private function exists(Company $company)
    {
        return $company
            ->query()
            ->where('id', '!=', $company->id)
            ->where(function ($query) use ($company) {
                $query->where('name', $company->name)
                    ->orWhere('vat', $company->vat);
            })
            ->exists();
    }
}
