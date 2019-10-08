<?php namespace App\Observers;

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
        if($this->exists($company)) {
            throw new \ErrorException(__('company.exists_exception'));
        }
    }

    /**
     * Handle the Company "updating" event.
     *
     * @param Company $company
     * @return void
     */
    public function updating(Company $company)
    {
        if(app()->runningInConsole()) {
            return;
        }

        if($company->isSystem()) {
            throw new \ErrorException(__('company.system_updating_exception'));
        }
    }

    /**
     * Handle the Company "deleting" event.
     *
     * @param Company $company
     * @return void
     */
    public function deleting(Company $company)
    {
        if(app()->runningInConsole()) {
            return;
        }

        if($company->isSystem()) {
            throw new \ErrorException(__('company.system_deleting_exception'));
        }
    }

    private function exists(Company $company)
    {
        return $company
            ->where('id', '!=', $company->id)
            ->where(function ($query) {
                $query->where('user_id', request()->user()->id ?? null)
                    ->orWhere('is_system', true);
            })
            ->where(function ($query) use ($company) {
                $query->where('name', $company->name)
                    ->orWhere('vat', $company->vat);
            })
            ->exists();
    }
}
