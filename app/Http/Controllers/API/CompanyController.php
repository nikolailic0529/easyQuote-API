<?php namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories \ {
    CompanyRepositoryInterface as CompanyRepository,
    VendorRepositoryInterface as VendorRepository
};
use App\Http\Requests\Company \ {
    StoreCompanyRequest,
    UpdateCompanyRequest
};
use Cache;

class CompanyController extends Controller
{
    protected $company;

    public function __construct(CompanyRepository $company, VendorRepository $vendor)
    {
        $this->company = $company;
        $this->vendor = $vendor;
    }

    /**
     * Display a listing of the Companies.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
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
     * @param  string $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        return response()->json(
            $this->company->find($id)
        );
    }

    /**
     * Update the specified Company in storage.
     *
     * @param  UpdateCompanyRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCompanyRequest $request, string $id)
    {
        return response()->json(
            $this->company->update($request, $id)
        );
    }

    /**
     * Remove the specified Company from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->company->delete($id)
        );
    }
    /**
     * Activate the specified Company from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->company->activate($id)
        );
    }

    /**
     * Deactivate the specified Company from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->company->deactivate($id)
        );
    }
}
