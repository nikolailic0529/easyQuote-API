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
use App\Http\Resources\UserRepositoryCollection;
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
     * @return \App\Models\User
     */
    public function updateOwnProfile(UpdateProfileRequest $request): User;

    /**
     * Get Collaboration User by id.
     *
     * @param string $id
     * @return \App\Models\User
     */
    public function find(string $id): User;

    /**
     * Find User by specified Email.
     *
     * @param string $email
     * @return \App\Models\User|null
     */
    public function findByEmail(string $email);

    /**
     * Retrieve a random user.
     *
     * @return \App\Models\User
     */
    public function random(): User;

    /**
     * Get all Collaboration Users.
     *
     * @return mixed
     */
    public function all();

    /**
     * Retrieve the list of users without pagination.
     *
     * @return mixed
     */
    public function list();

    /**
     * Search over Collaboration Users.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

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
     * Retrieve Failure Report Recepients.
     *
     * @return IlluminateCollection
     */
    public function failureReportRecepients(): IlluminateCollection;

    /**
     * Reset Password for specified User.
     *
     * @param StoreResetPasswordRequest $request
     * @param string $id
     * @return bool
     */
    public function resetPassword(StoreResetPasswordRequest $request, string $id): bool;

    /**
     * Reset specified User (set already_logged_in to 0).
     *
     * @param string $id
     * @return bool
     */
    public function resetAccount(string $id): bool;

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

    /**
     * Map Resource into UserRepositoryCollection.
     *
     * @param mixed $resource
     * @return \App\Http\Resources\UserRepositoryCollection
     */
    public function toCollection($resource): UserRepositoryCollection;
}
