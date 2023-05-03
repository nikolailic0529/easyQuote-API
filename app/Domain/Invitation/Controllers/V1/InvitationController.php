<?php

namespace App\Domain\Invitation\Controllers\V1;

use App\Domain\Invitation\Contracts\InvitationRepositoryInterface as Invitations;
use App\Domain\Invitation\Models\Invitation;
use App\Domain\Invitation\Resources\V1\InvitationCollection;
use App\Domain\Invitation\Resources\V1\InvitationResource;
use App\Foundation\Http\Controller;

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
     * @return void
     */
    public function destroy(Invitation $invitation)
    {
        return response()->json(
            $this->invitation->delete($invitation->invitation_token)
        );
    }
}
