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
        if(app()->runningInConsole()) {
            return;
        }

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
        //
    }

    /**
     * Handle the Company "deleting" event.
     *
     * @param Company $company
     * @return void
     */
    public function deleting(Company $company)
    {
        //
    }

    private function exists(Company $company)
    {
        return $company
            ->query()
            ->userCollaboration()
            ->where('id', '!=', $company->id)
            ->where(function ($query) use ($company) {
                $query->where('name', $company->name)
                    ->orWhere('vat', $company->vat);
            })
            ->exists();
    }
}
