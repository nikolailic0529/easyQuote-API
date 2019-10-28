<?php namespace App\Contracts\Repositories;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\Collaboration \ {
    InviteUserRequest,
    UpdateUserRequest
};
use App\Http\Requests\UserSignUpRequest;
use App\Models \ {
    User,
    Collaboration\Invitation
};
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    /**
     * Data for new User Invitation
     *
     * @return Collection
     */
    public function data(): Collection;

    /**
     * Make a new User.
     *
     * @param array $array
     * @return User
     */
    public function make(array $array): User;

    /**
     * Invite a new User for Collaboration with specified Role.
     *
     * @param \App\Http\Requests\Collaboration\InviteUserRequest $request
     * @return bool
     */
    public function invite(InviteUserRequest $request): bool;

    /**
     * Find specified Invitation by Unique Token
     *
     * @param string $token
     * @return array
     */
    public function invitation(string $token): Invitation;

    /**
     * Create a new User
     *
     * @param array $attributes
     * @return \App\Models\User
     */
    public function create(array $attributes): User;

    /**
     * Create a new User by Invitation
     *
     * @param array $attributes
     * @param \App\Models\Collaboration\Invitation $invitation
     * @return \App\Models\User
     */
    public function completeInvitation(array $attributes, Invitation $invitation): User;

    /**
     * Update Collaboration User
     *
     * @param \App\Http\Requests\Collaboration\UpdateUserRequest $request
     * @return bool
     */
    public function update(UpdateUserRequest $request): bool;

    /**
     * Get Collaboration User by id
     *
     * @param string $id
     * @return \App\Models\User
     */
    public function find(string $id): User;

    /**
     * Get all Collaboration Users.
     *
     * @return Paginator
     */
    public function all(): Paginator;

    /**
     * Search over Collaboration Users.
     *
     * @param string $query
     * @return Paginator
     */
    public function search(string $query = ''): Paginator;

    /**
     * Collaboration Users query.
     *
     * @return Builder
     */
    public function userQuery(): Builder;
}
