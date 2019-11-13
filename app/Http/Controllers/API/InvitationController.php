<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\InvitationRepositoryInterface as InvitationRepository;
use App\Http\Requests\Collaboration\InviteUserRequest;
use App\Models\Collaboration\Invitation;

class InvitationController extends Controller
{
    protected $invitation;

    public function __construct(InvitationRepository $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Display a listing of the invitation.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->filled('search')) {
            return response()->json(
                $this->invitation->search(request('search'))
            );
        }

        return response()->json(
            $this->invitation->all()
        );
    }

    /**
     * Store a newly created invitation in storage.
     *
     * @param  \App\Http\Requests\Collaboration\InviteUserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(InviteUserRequest $request)
    {
        return response()->json(
            $this->invitation->create($request)
        );
    }

    /**
     * Display the specified invitation.
     *
     * @param  \App\Models\Collaboration\Invitation  $invitation
     * @return \Illuminate\Http\Response
     */
    public function show(Invitation $invitation)
    {
        return response()->json(
            $this->invitation->find($invitation->invitation_token)
        );
    }

    /**
     * Cancel the specified invitation.
     *
     * @param  \App\Models\Collaboration\Invitation  $invitation
     * @return \Illuminate\Http\Response
     */
    public function cancel(Invitation $invitation)
    {
        return response()->json(
            $this->invitation->cancel($invitation->invitation_token)
        );
    }

    /**
     * Resend the specified invitation.
     *
     * @param  \App\Models\Collaboration\Invitation  $invitation
     * @return \Illuminate\Http\Response
     */
    public function resend(Invitation $invitation)
    {
        return response()->json(
            $this->invitation->resend($invitation->invitation_token)
        );
    }

    /**
     * Remove the specified Invitation from storage.
     *
     * @param Invitation $invitation
     * @return void
     */
    public function destroy(Invitation $invitation)
    {
        return response()->json(
            $this->invitation->delete($invitation->invitation_token)
        );
    }
}
