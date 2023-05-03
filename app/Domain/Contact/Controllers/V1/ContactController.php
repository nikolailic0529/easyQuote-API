<?php

namespace App\Domain\Contact\Controllers\V1;

use App\Domain\Address\Models\Address;
use App\Domain\Appointment\Queries\AppointmentQueries;
use App\Domain\Appointment\Resources\V1\AppointmentListResource;
use App\Domain\Contact\Models\Contact;
use App\Domain\Contact\Queries\ContactQueries;
use App\Domain\Contact\Requests\{UpdateContactRequest};
use App\Domain\Contact\Requests\StoreContactRequest;
use App\Domain\Contact\Resources\V1\ContactListResource;
use App\Domain\Contact\Services\ContactEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Contact::class, 'contact');
    }

    /**
     * Display a listing of available contacts.
     */
    public function index(Request $request, ContactQueries $queries): AnonymousResourceCollection
    {
        /** @var LengthAwarePaginator $pagination */
        $pagination = $queries->listOfContactsQuery($request)->apiPaginate();

        return tap(ContactListResource::collection($pagination),
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
     * Store a newly created contact in storage.
     */
    public function store(
        StoreContactRequest $request,
        ContactEntityService $entityService
    ): JsonResponse {
        $resource = $entityService
            ->setCauser($request->user())
            ->createContact($request->getCreateContactData())
            ->withAppends();

        return response()->json(
            $resource
        );
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact): JsonResponse
    {
        return response()->json(
            $contact->withAppends()
        );
    }

    /**
     * Update the specified contact in storage.
     *
     * @param \App\Domain\Contact\Requests\UpdateContactRequest $request
     */
    public function update(
        UpdateContactRequest $request,
        ContactEntityService $entityService,
        Contact $contact
    ): JsonResponse {
        $resource = $entityService
            ->setCauser($request->user())
            ->updateContact($contact, $request->getUpdateContactData())
            ->withAppends();

        return response()->json(
            $resource
        );
    }

    /**
     * Associate contact with address.
     *
     * @throws AuthorizationException
     */
    public function associateContactWithAddress(
        Request $request,
        ContactEntityService $entityService,
        Contact $contact,
        Address $address
    ): JsonResponse {
        $this->authorize('update', $contact);

        $resource = $entityService
            ->setCauser($request->user())
            ->associateContactWithAddress($contact, $address)
            ->withAppends();

        return response()->json(
            $resource
        );
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(
        Request $request,
        ContactEntityService $entityService,
        Contact $contact
    ): JsonResponse {
        $entityService
            ->setCauser($request->user())
            ->deleteContact($contact);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Activate the specified contact.
     *
     * @throws AuthorizationException
     */
    public function activate(
        Request $request,
        ContactEntityService $entityService,
        Contact $contact
    ): JsonResponse {
        $this->authorize('update', $contact);

        $entityService
            ->setCauser($request->user())
            ->markContactAsActive($contact);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Deactivate the specified contact.
     *
     * @throws AuthorizationException
     */
    public function deactivate(
        Request $request,
        ContactEntityService $entityService,
        Contact $contact
    ): JsonResponse {
        $this->authorize('update', $contact);

        $entityService
            ->setCauser($request->user())
            ->markContactAsInactive($contact);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * List appointments linked to contact.
     *
     * @throws AuthorizationException
     */
    public function showAppointmentsOfContact(
        Request $request,
        AppointmentQueries $appointmentQueries,
        Contact $contact
    ): AnonymousResourceCollection {
        $this->authorize('view', $contact);

        $resource = $appointmentQueries->listAppointmentsLinkedToQuery($contact, $request)->get();

        return AppointmentListResource::collection($resource);
    }
}
