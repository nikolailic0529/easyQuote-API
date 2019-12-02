<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\ContactRepositoryInterface as ContactRepository;
use App\Models\Contact;
use App\Http\Requests\Contact\{
    StoreContactRequest,
    UpdateContactRequest
};

class ContactController extends Controller
{
    protected $contact;

    public function __construct(ContactRepository $contact)
    {
        $this->contact = $contact;
        $this->authorizeResource(Contact::class, 'contact');
    }

    /**
     * Display a listing of the contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            request()->filled('search')
                ? $this->contact->search(request('search'))
                : $this->contact->all()
        );
    }

    /**
     * Store a newly created contact in storage.
     *
     * @param  \App\Http\Requests\Contact\StoreContactRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreContactRequest $request)
    {
        return response()->json(
            $this->contact->create($request)
        );
    }

    /**
     * Display the specified contact.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact)
    {
        return response()->json(
            $this->contact->find($contact->id)
        );
    }

    /**
     * Update the specified contact in storage.
     *
     * @param  \App\Http\Requests\Contact\UpdateContactRequest  $request
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateContactRequest $request, Contact $contact)
    {
        return response()->json(
            $this->contact->update($request, $contact->id)
        );
    }

    /**
     * Remove the specified contact from storage.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact)
    {
        return response()->json(
            $this->contact->delete($contact->id)
        );
    }

    /**
     * Activate the specified contact.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function activate(Contact $contact)
    {
        $this->authorize('update', $contact);

        return response()->json(
            $this->contact->activate($contact->id)
        );
    }

    /**
     * Deactivate the specified contact.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Contact $contact)
    {
        $this->authorize('update', $contact);

        return response()->json(
            $this->contact->deactivate($contact->id)
        );
    }
}
