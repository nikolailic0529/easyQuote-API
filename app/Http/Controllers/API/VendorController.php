<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\VendorRepositoryInterface as VendorRepository;
use App\Http\Requests\Vendor\{
    StoreVendorRequest,
    UpdateVendorRequest
};
use App\Models\Vendor;

class VendorController extends Controller
{
    protected $vendor;

    public function __construct(VendorRepository $vendor)
    {
        $this->vendor = $vendor;
        $this->authorizeResource(Vendor::class, 'vendor');
    }

    /**
     * Display a listing of the Vendors.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->filled('search')) {
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
     * @param  \App\Models\Vendor $vendor
     * @return \Illuminate\Http\Response
     */
    public function show(Vendor $vendor)
    {
        return response()->json(
            $this->vendor->find($vendor->id)
        );
    }

    /**
     * Update the specified Vendor in storage.
     *
     * @param  UpdateVendorRequest  $request
     * @param  \App\Models\Vendor $vendor
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        return response()->json(
            $this->vendor->update($request, $vendor->id)
        );
    }

    /**
     * Remove the specified Vendor from storage.
     *
     * @param  \App\Models\Vendor $vendor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Vendor $vendor)
    {
        return response()->json(
            $this->vendor->delete($vendor->id)
        );
    }

    /**
     * Activate the specified Vendor from storage.
     *
     * @param  \App\Models\Vendor $vendor
     * @return \Illuminate\Http\Response
     */
    public function activate(Vendor $vendor)
    {
        $this->authorize('update', $vendor);

        return response()->json(
            $this->vendor->activate($vendor->id)
        );
    }

    /**
     * Deactivate the specified Vendor from storage.
     *
     * @param  \App\Models\Vendor $vendor
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Vendor $vendor)
    {
        $this->authorize('update', $vendor);

        return response()->json(
            $this->vendor->deactivate($vendor->id)
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
        $this->authorize('viewAny', Vendor::class);

        return response()->json(
            $this->vendor->country($id)
        );
    }
}
