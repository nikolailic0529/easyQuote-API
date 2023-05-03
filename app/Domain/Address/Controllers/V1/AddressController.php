<?php

namespace App\Domain\Address\Controllers\V1;

use App\Domain\Address\Models\Address;
use App\Domain\Address\Queries\AddressQueries;
use App\Domain\Address\Requests\StoreAddressRequest;
use App\Domain\Address\Requests\UpdateAddressRequest;
use App\Domain\Address\Resources\V1\AddressListResource;
use App\Domain\Address\Services\AddressEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Address::class, 'address');
    }

    /**
     * Paginate addresses.
     */
    public function index(Request $request, AddressQueries $queries): AnonymousResourceCollection
    {
        /** @var LengthAwarePaginator $pagination */
        $pagination = $queries->listOfAddressesQuery($request)->apiPaginate();

        return tap(AddressListResource::collection($pagination),
            static function (AnonymousResourceCollection $resourceCollection) use ($pagination): void {
                $resourceCollection->additional([
                    'current_page' => $pagination->currentPage(),
                    'from' => $pagination->firstItem(),
                    'to' => $pagination->lastItem(),
                    'last_page' => $pagination->lastPage(),
                    'path' => $pagination->path(),
                    'per_page' => $pagination->perPage(),
                    'total' => $pagination->total(),
                ]);
            });
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
