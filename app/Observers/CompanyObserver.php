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

        abort_if($this->exists($company), 409, __('company.exists_exception'));
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

        abort_if($company->inUse(), 409, __('company.in_use_deleting_exception'));
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
