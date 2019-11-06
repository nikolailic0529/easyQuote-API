<?php namespace App\Contracts\Repositories;

use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Collaboration \ {
    InviteUserRequest
};
use App\Models\Collaboration\Invitation;

interface InvitationRepositoryInterface
{
    /**
     * Get all Invitations.
     *
     * @return Paginator
     */
    public function all(): Paginator;

    /**
     * Search over Invitations.
     *
     * @param string $query
     * @return Paginator
     */
    public function search(string $query = ''): Paginator;

    /**
     * Invitations query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find Invitation.
     *
     * @param string $id
     * @return Invitation
     */
    public function find(string $id): Invitation;

    /**
     * Resend Invitation.
     *
     * @param string $id
     * @return boolean
     */
    public function resend(string $id): bool;

    /**
     * Cancel Invitation.
     *
     * @param string $id
     * @return boolean
     */
    public function cancel(string $id): bool;
}
