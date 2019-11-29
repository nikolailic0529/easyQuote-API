<?php

namespace App\Contracts\Repositories;

use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Collaboration\Invitation;

interface InvitationRepositoryInterface
{
    /**
     * Get all Invitations.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search over Invitations.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Invitations query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Find specified Invitation.
     *
     * @param string $id
     * @return Invitation
     */
    public function find(string $id): Invitation;

    /**
     * Resend specified Invitation.
     *
     * @param string $id
     * @return bool
     */
    public function resend(string $id): bool;

    /**
     * Cancel specified Invitation.
     *
     * @param string $id
     * @return bool
     */
    public function cancel(string $id): bool;

    /**
     * Delete specified Invitation.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;
}
