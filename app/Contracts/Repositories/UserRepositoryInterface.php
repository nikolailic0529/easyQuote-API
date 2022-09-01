<?php

namespace App\Contracts\Repositories;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\StoreResetPasswordRequest;
use App\Http\Requests\UpdateCurrentUserRequest;
use App\Http\Resources\V1\UserRepositoryCollection;
use App\Models\{Collaboration\Invitation, User};
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DbCollection;
use Illuminate\Support\LazyCollection;

interface UserRepositoryInterface
{
    /**
     * Make a new User.
     *
     * @param array $array
     * @return User
     */
    public function make(array $array): User;

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
     * Update the specific user.
     *
     * @param string $id
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function update(string $id, array $attributes, array $options = []): bool;

    /**
     * Update the users by specific scope.
     *
     * @param array $attributes
     * @param array $where
     * @return integer
     */
    public function updateWhere(array $attributes, array $where = []): int;

    /**
     * Pluck user attributes by specific clause.
     *
     * @param  array $where
     * @param  string $column
     * @param  string|null $key
     * @return array
     */
    public function pluckWhere(array $where, $column, $key = null): array;

    /**
     * Increment the given user attribute.
     *
     * @param string $id
     * @param string $attribute
     * @param array $options
     * @return boolean
     */
    public function increment(string $id, string $attribute, array $options = []): bool;

    /**
     * Find the User by id.
     *
     * @param string $id
     * @return \App\Models\User
     */
    public function find(string $id): User;

    /**
     * Find the User by id and lock a record for update.
     * 
     * @param  string $id
     * @return User|null
     */
    public function findWithLock(string $id): ?User;

    /**
     * Find User by specified Email.
     *
     * @param string|array $email
     * @return \App\Models\User|\Illuminate\Database\Eloquent\Collection|null
     */
    public function findByEmail($email);

    /**
     * Find user by email like given needle.
     *
     * @param string $email
     * @return void
     */
    public function findByEmailLike(string $email);

    /**
     * Retrieve many users by specified ids.
     *
     * @param array $ids
     * @return DbCollection
     */
    public function findMany(array $ids): DbCollection;

    /**
     * Retrieve users by specific roles.
     *
     * @param array $roles
     * @param Closure|null $closure
     * @return mixed
     */
    public function findByRoles(array $roles, ?Closure $closure = null);

    /**
     * Retrieve a random user.
     *
     * @return \App\Models\User
     */
    public function random(): User;

    /**
     * Retrieve authenticated users with the same ip.
     *
     * @param  string $ip
     * @return DbCollection
     */
    public function findAuthenticatedUsersByIp(string $ip, array $columns = ['*']): DbCollection;

    /**
     * Determine that user with the same ip is authenticated.
     *
     * @param string $excludedId
     * @param string $ip
     * @return bool
     */
    public function authenticatedIpExists(string $excludedId, string $ip): bool;

    /**
     * Determine that user with the same ip is not authenticated.
     *
     * @param string $excludedId
     * @param string $ip
     * @return boolean
     */
    public function authenticatedIpDoesntExist(string $excludedId, string $ip): bool;

    /**
     * Get all Collaboration Users.
     *
     * @return mixed
     */
    public function all();

    /**
     * Retrieve a list of the existing users.
     *
     * @param array $columns
     * @return mixed
     */
    public function list(array $columns = ['*']);

    /**
     * Retrieve a list of the existing users excluding given user id.
     *
     * @param string $id
     * @param array $columns
     * @return mixed
     */
    public function exclusiveList(string $id, array $columns = ['*']);

    /**
     * Iterate throw the existing users using a cursor.
     *
     * @param \Closure $scope
     * @return \Illuminate\Support\LazyCollection
     */
    public function cursor(?Closure $scope = null): LazyCollection;

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
     * @return DbCollection
     */
    public function administrators(): DbCollection;

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
     * Get users have specific permission.
     *
     * @param string $permission
     * @return mixed
     */
    public function getUsersWithPermission(string $permission);

    /**
     * Sync users permissions with the given.
     * Given permission will be granted to passed users and revoked from users which are not passed.
     *
     * @param array $ids
     * @param string $permission
     * @return array
     */
    public function syncUsersPermission(array $ids, string $permission): array;

    /**
     * Give specific permission to user.
     *
     * @param string $permissionName
     * @param User $user
     * @return boolean
     */
    public function givePermissionTo(string $permissionName, User $user): bool;

    /**
     * Revoke specific permission from user.
     *
     * @param string $permissionName
     * @param User $user
     * @return boolean
     */
    public function revokePermissionTo(string $permissionName, User $user): bool;

    /**
     * Map Resource into UserRepositoryCollection.
     *
     * @param mixed $resource
     * @return \App\Http\Resources\V1\UserRepositoryCollection
     */
    public function toCollection($resource): UserRepositoryCollection;
}
