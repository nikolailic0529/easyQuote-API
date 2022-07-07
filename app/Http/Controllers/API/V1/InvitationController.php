<?php

namespace App\Http\Controllers\API\V1;

use App\Contracts\Repositories\InvitationRepositoryInterface as Invitations;
use App\Http\Controllers\Controller;
use App\Http\Resources\{V1\Invitation\InvitationCollection, V1\Invitation\InvitationResource,};
use App\Models\Collaboration\Invitation;

class InvitationController extends Controller
{
    protected Invitations $invitation;

    public function __construct(Invitations $invitation)
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
        $resource = request()->filled('search')
            ? $this->invitation->search(request('search'))
            : $this->invitation->all();

        return response()->json(
            InvitationCollection::make($resource)
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
            InvitationResource::make($invitation)
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
