<?php

namespace App\Domain\Vendor\Controllers\V1;

use App\Domain\Vendor\Contracts\VendorRepositoryInterface as VendorRepository;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Vendor\Queries\VendorQueries;
use App\Domain\Vendor\Requests\StoreVendorRequest;
use App\Domain\Vendor\Requests\UpdateVendorRequest;
use App\Domain\Vendor\Resources\V1\VendorCollection;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;

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
        return response()->json(
            request()->filled('search')
                ? $this->vendor->search(request('search'))
                : $this->vendor->all()
        );
    }

    /**
     * Show the vendors listing.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showVendorsList(VendorQueries $queries): JsonResponse
    {
        $this->authorize('viewList', Vendor::class);

        return response()->json(
            VendorCollection::make($queries->listingQuery()->get())
        );
    }

    /**
     * Store a newly created Vendor in storage.
     *
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
     * @return \Illuminate\Http\Response
     */
    public function show(Vendor $vendor)
    {
        return response()->json(
            $vendor->appendLogo()->load('countries:id,name,iso_3166_2,flag')
        );
    }

    /**
     * Update the specified Vendor in storage.
     *
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
     * Find the specified Vendors by Country.
     *
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
