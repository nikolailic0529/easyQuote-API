<?php

namespace App\Domain\Invitation\Contracts;

use App\Domain\Invitation\Models\Invitation;
use Illuminate\Database\Eloquent\Builder;

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
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Invitations query.
     */
    public function userQuery(): Builder;

    /**
     * Find specified Invitation.
     */
    public function find(string $id): Invitation;

    /**
     * Resend specified Invitation.
     */
    public function resend(string $id): bool;

    /**
     * Cancel specified Invitation.
     */
    public function cancel(string $id): bool;

    /**
     * Delete specified Invitation.
     */
    public function delete(string $id): bool;
}
