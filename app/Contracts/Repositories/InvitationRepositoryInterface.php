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
