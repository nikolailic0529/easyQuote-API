<?php namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\VendorRepositoryInterface as VendorRepository;
use App\Http\Requests\Vendor \ {
    StoreVendorRequest,
    UpdateVendorRequest
};

class VendorController extends Controller
{
    protected $vendor;

    public function __construct(VendorRepository $vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * Display a listing of the User's Vendors.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->vendor->search(request('search'))
            );
        }

        return response()->json(
            $this->vendor->all()
        );
    }

    /**
     * Store a newly created Vendor in storage.
     *
     * @param  StoreVendorRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVendorRequest $request)
    {
        return response()->json(
            $this->vendor->create($request)
        );
    }

    /**
     * Display the specified Vendor.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        return response()->json(
            $this->vendor->find($id)
        );
    }

    /**
     * Update the specified Vendor in storage.
     *
     * @param  UpdateVendorRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVendorRequest $request, string $id)
    {
        return response()->json(
            $this->vendor->update($request, $id)
        );
    }

    /**
     * Remove the specified Vendor from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->vendor->delete($id)
        );
    }

    /**
     * Activate the specified Vendor from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->vendor->activate($id)
        );
    }

    /**
     * Deactivate the specified Vendor from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->vendor->deactivate($id)
        );
    }

    /**
     * Find the specified Vendors by Country
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function country(string $id)
    {
        return response()->json(
            $this->vendor->country($id)
        );
    }
}
