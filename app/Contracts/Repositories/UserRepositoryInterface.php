<?php

namespace App\Contracts\Repositories;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\Collaboration\{
    InviteUserRequest,
    UpdateUserRequest
};
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\StoreResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\{
    User,
    Collaboration\Invitation
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as IlluminateCollection;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    /**
     * Data for new User Invitation.
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
     * Find specified Invitation by Unique Token.
     *
     * @param string $token
     * @return array
     */
    public function invitation(string $token): Invitation;

    /**
     * Create a new User.
     *
     * @param array $attributes
     * @return \App\Models\User
     */
    public function create(array $attributes): User;

    /**
     * Create a new User with Administrator Role.
     *
     * @param array $attributes
     * @return User
     */
    public function createAdministrator(array $attributes): User;

    /**
     * Create a new User by Invitation.
     *
     * @param array $attributes
     * @param \App\Models\Collaboration\Invitation $invitation
     * @return \App\Models\User
     */
    public function createCollaborator(array $attributes, Invitation $invitation): User;

    /**
     * Update Collaboration User.
     *
     * @param \App\Http\Requests\Collaboration\UpdateUserRequest $request
     * @param string $id
     * @return bool
     */
    public function update(UpdateUserRequest $request, string $id): bool;

    /**
     * Update Current Authenticated User's Profile.
     *
     * @param UpdateProfileRequest $request
     * @return bool
     */
    public function updateOwnProfile(UpdateProfileRequest $request): bool;

    /**
     * Get Collaboration User by id.
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

    /**
     * Activate specified Collaboration User.
     *
     * @param string $id
     * @return bool
     */
    public function activate(string $id): bool;

    /**
     * Deactivate specified Collaboration User.
     *
     * @param string $id
     * @return bool
     */
    public function deactivate(string $id): bool;

    /**
     * Delete specified Collaboration User.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Retrieve All Users who have Administrator role.
     *
     * @return IlluminateCollection
     */
    public function administrators(): IlluminateCollection;

    /**
     * Reset Password for specified User.
     *
     * @param StoreResetPasswordRequest $request
     * @param string $id
     * @return bool
     */
    public function resetPassword(StoreResetPasswordRequest $request, string $id): bool;

    /**
     * Perform Intitiated Password Reset.
     *
     * @param PasswordResetRequest $request
     * @param string $token
     * @return bool
     */
    public function performResetPassword(PasswordResetRequest $request, string $token): bool;

    /**
     * Verify the specified PasswordReset Token.
     *
     * @param string $token
     * @return bool
     */
    public function verifyPasswordReset(string $token): bool;
}
