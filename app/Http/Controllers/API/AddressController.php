<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\{StoreAddressRequest, UpdateAddressRequest};
use App\Models\Address;
use App\Queries\AddressQueries;
use App\Services\Address\AddressEntityService;
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
     *
     * @param Request $request
     * @param AddressQueries $queries
     * @return JsonResponse
     */
    public function index(Request $request, AddressQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->listOfAddressesQuery($request)->apiPaginate(),
        );
    }

    /**
     * Store a newly created address in storage.
     *
     * @param StoreAddressRequest $request
     * @param AddressEntityService $entityService
     * @return JsonResponse
     */
    public function store(StoreAddressRequest  $request,
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
     *
     * @param Address $address
     * @return JsonResponse
     */
    public function show(Address $address): JsonResponse
    {
        return response()->json(
            $address->loadMissing('country')
        );
    }

    /**
     * Update the specified address in storage.
     *
     * @param UpdateAddressRequest $request
     * @param AddressEntityService $entityService
     * @param Address $address
     * @return JsonResponse
     */
    public function update(UpdateAddressRequest $request,
                           AddressEntityService $entityService,
                           Address              $address): JsonResponse
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
     *
     * @param Request $request
     * @param AddressEntityService $entityService
     * @param Address $address
     * @return JsonResponse
     */
    public function destroy(Request              $request,
                            AddressEntityService $entityService,
                            Address              $address): JsonResponse
    {
        $entityService
            ->setCauser($request->user())
            ->deleteAddress($address);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Activate the specified address.
     *
     * @param Request $request
     * @param AddressEntityService $entityService
     * @param Address $address
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function activate(Request              $request,
                             AddressEntityService $entityService,
                             Address              $address): JsonResponse
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
     * @param Request $request
     * @param AddressEntityService $entityService
     * @param Address $address
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deactivate(Request              $request,
                               AddressEntityService $entityService,
                               Address              $address): JsonResponse
    {
        $this->authorize('update', $address);

        $entityService
            ->setCauser($request->user())
            ->markAddressAsInactive($address);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
