<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\{
    CompanyRepositoryInterface as CompanyRepository,
    VendorRepositoryInterface as VendorRepository
};
use App\Http\Requests\Company\{
    StoreCompanyRequest,
    UpdateCompanyRequest
};
use App\Models\Company;

class CompanyController extends Controller
{
    protected $company;

    public function __construct(CompanyRepository $company, VendorRepository $vendor)
    {
        $this->company = $company;
        $this->vendor = $vendor;
        $this->authorizeResource(Company::class, 'company');
    }

    /**
     * Display a listing of the Companies.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->filled('search')) {
            return response()->json(
                $this->company->search(request('search'))
            );
        }

        return response()->json(
            $this->company->all()
        );
    }

    /**
     * Data for creating a new Company.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $vendors = $this->vendor->allFlatten();

        return response()->json(
            $this->company->data(compact('vendors'))
        );
    }

    /**
     * Store a newly created Company in storage.
     *
     * @param  StoreCompanyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCompanyRequest $request)
    {
        return response()->json(
            $this->company->create($request)
        );
    }

    /**
     * Display the specified Company.
     *
     * @param  \App\Models\Company $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        return response()->json(
            $this->company->find($company->id)
        );
    }

    /**
     * Update the specified Company in storage.
     *
     * @param  UpdateCompanyRequest  $request
     * @param  \App\Models\Company $company
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        return response()->json(
            $this->company->update($request, $company->id)
        );
    }

    /**
     * Remove the specified Company from storage.
     *
     * @param  \App\Models\Company $company
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        return response()->json(
            $this->company->delete($company->id)
        );
    }

    /**
     * Activate the specified Company from storage.
     *
     * @param  \App\Models\Company $company
     * @return \Illuminate\Http\Response
     */
    public function activate(Company $company)
    {
        $this->authorize('update', $company);

        return response()->json(
            $this->company->activate($company->id)
        );
    }

    /**
     * Deactivate the specified Company from storage.
     *
     * @param  \App\Models\Company $company
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Company $company)
    {
        $this->authorize('update', $company);

        return response()->json(
            $this->company->deactivate($company->id)
        );
    }
}
