<?php

namespace App\Domain\Address\Controllers\V1;

use App\Domain\Address\Models\Address;
use App\Domain\Address\Queries\AddressQueries;
use App\Domain\Address\Requests\StoreAddressRequest;
use App\Domain\Address\Requests\UpdateAddressRequest;
use App\Domain\Address\Services\AddressEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Address::class, 'address');
    }

    /**
     * Display a listing of the address.
     */
    public function index(Request $request, AddressQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->listOfAddressesQuery($request)->apiPaginate(),
        );
    }

    /**
     * Store a newly created address in storage.
     */
    public function store(StoreAddressRequest $request,
                          AddressEntityService $entityService): JsonResponse
    {
        $resource = $entityService
            ->setCauser($request->user())
            ->createAddress($request->getCreateAddressData())
            ->loadMissing('country');

        return response()->json(
            $resource
        );
    }

    /**
     * Display the specified address.
     */
    public function show(Address $address): JsonResponse
    {
        return response()->json(
            $address->loadMissing('country')
        );
    }

    /**
     * Update the specified address in storage.
     */
    public function update(UpdateAddressRequest $request,
                           AddressEntityService $entityService,
                           Address $address): JsonResponse
    {
        $resource = $entityService
            ->setCauser($request->user())
            ->updateAddress($address, $request->getUpdateAddressData())
            ->loadMissing('country');

        return response()->json(
            $resource
        );
    }

    /**
     * Remove the specified address from storage.
     */
    public function destroy(Request $request,
                            AddressEntityService $entityService,
                            Address $address): JsonResponse
    {
        $entityService
            ->setCauser($request->user())
            ->deleteAddress($address);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Activate the specified address.
     *
     * @throws AuthorizationException
     */
    public function activate(Request $request,
                             AddressEntityService $entityService,
                             Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $entityService
            ->setCauser($request->user())
            ->markAddressAsActive($address);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Deactivate the specified address.
     *
     * @throws AuthorizationException
     */
    public function deactivate(Request $request,
                               AddressEntityService $entityService,
                               Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $entityService
            ->setCauser($request->user())
            ->markAddressAsInactive($address);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
