<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\{StoreContactRequest, UpdateContactRequest};
use App\Http\Resources\V1\Appointment\AppointmentListResource;
use App\Models\Contact;
use App\Queries\AppointmentQueries;
use App\Queries\ContactQueries;
use App\Services\Contact\ContactEntityService;
use Illuminate\Auth\Access\AuthorizationException;
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
     *
     * @param Request $request
     * @param ContactQueries $queries
     * @return JsonResponse
     */
    public function index(Request $request, ContactQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->listOfContactsQuery($request)->apiPaginate()
        );
    }

    /**
     * Store a newly created contact in storage.
     *
     * @param StoreContactRequest $request
     * @param ContactEntityService $entityService
     * @return JsonResponse
     */
    public function store(StoreContactRequest  $request,
                          ContactEntityService $entityService): JsonResponse
    {
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
     *
     * @param Contact $contact
     * @return JsonResponse
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
     * @param UpdateContactRequest $request
     * @param ContactEntityService $entityService
     * @param Contact $contact
     * @return JsonResponse
     */
    public function update(UpdateContactRequest $request,
                           ContactEntityService $entityService,
                           Contact              $contact): JsonResponse
    {
        $resource = $entityService
            ->setCauser($request->user())
            ->updateContact($contact, $request->getUpdateContactData())
            ->withAppends();

        return response()->json(
            $resource
        );
    }

    /**
     * Remove the specified contact from storage.
     *
     * @param Request $request
     * @param ContactEntityService $entityService
     * @param Contact $contact
     * @return JsonResponse
     */
    public function destroy(Request              $request,
                            ContactEntityService $entityService,
                            Contact              $contact): JsonResponse
    {
        $entityService
            ->setCauser($request->user())
            ->deleteContact($contact);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Activate the specified contact.
     *
     * @param Request $request
     * @param ContactEntityService $entityService
     * @param Contact $contact
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function activate(Request              $request,
                             ContactEntityService $entityService,
                             Contact              $contact): JsonResponse
    {
        $this->authorize('update', $contact);

        $entityService
            ->setCauser($request->user())
            ->markContactAsActive($contact);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Deactivate the specified contact.
     *
     * @param Request $request
     * @param ContactEntityService $entityService
     * @param Contact $contact
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deactivate(Request              $request,
                               ContactEntityService $entityService,
                               Contact              $contact): JsonResponse
    {
        $this->authorize('update', $contact);

        $entityService
            ->setCauser($request->user())
            ->markContactAsInactive($contact);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * List appointments linked to contact.
     *
     * @param Request $request
     * @param AppointmentQueries $appointmentQueries
     * @param Contact $contact
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAppointmentsOfContact(Request            $request,
                                              AppointmentQueries $appointmentQueries,
                                              Contact            $contact): AnonymousResourceCollection
    {
        $this->authorize('view', $contact);

        $resource = $appointmentQueries->listAppointmentsLinkedToQuery($contact, $request)->get();

        return AppointmentListResource::collection($resource);
    }
}
