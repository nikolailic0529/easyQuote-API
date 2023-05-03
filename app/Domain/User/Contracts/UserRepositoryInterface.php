<?php

namespace App\Domain\User\Contracts;

use App\Domain\Authentication\Requests\PasswordResetRequest;
use App\Domain\Authentication\Requests\StoreResetPasswordRequest;
use App\Domain\Invitation\Models\Invitation;
use App\Domain\User\Models\User;
use App\Domain\User\Resources\V1\UserRepositoryCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DbCollection;
use Illuminate\Support\LazyCollection;

interface UserRepositoryInterface
{
    /**
     * Make a new User.
     */
    public function make(array $array): User;

    /**
     * Find specified Invitation by Unique Token.
     *
     * @return array
     */
    public function invitation(string $token): Invitation;

    /**
     * Create a new User.
     */
    public function create(array $attributes): User;

    /**
     * Create a new User with Administrator Role.
     */
    public function createAdministrator(array $attributes): User;

    /**
     * Create a new User by Invitation.
     */
    public function createCollaborator(array $attributes, Invitation $invitation): User;

    /**
     * Update the specific user.
     */
    public function update(string $id, array $attributes, array $options = []): bool;

    /**
     * Update the users by specific scope.
     */
    public function updateWhere(array $attributes, array $where = []): int;

    /**
     * Pluck user attributes by specific clause.
     *
     * @param string      $column
     * @param string|null $key
     */
    public function pluckWhere(array $where, $column, $key = null): array;

    /**
     * Increment the given user attribute.
     */
    public function increment(string $id, string $attribute, array $options = []): bool;

    /**
     * Find the User by id.
     */
    public function find(string $id): User;

    /**
     * Find the User by id and lock a record for update.
     */
    public function findWithLock(string $id): ?User;

    /**
     * Find User by specified Email.
     *
     * @param string|array $email
     *
     * @return \App\Domain\User\Models\User|\Illuminate\Database\Eloquent\Collection|null
     */
    public function findByEmail($email);

    /**
     * Find user by email like given needle.
     *
     * @return void
     */
    public function findByEmailLike(string $email);

    /**
     * Retrieve many users by specified ids.
     */
    public function findMany(array $ids): DbCollection;

    /**
     * Retrieve users by specific roles.
     *
     * @return mixed
     */
    public function findByRoles(array $roles, ?\Closure $closure = null);

    /**
     * Retrieve a random user.
     */
    public function random(): User;

    /**
     * Retrieve authenticated users with the same ip.
     */
    public function findAuthenticatedUsersByIp(string $ip, array $columns = ['*']): DbCollection;

    /**
     * Determine that user with the same ip is authenticated.
     */
    public function authenticatedIpExists(string $excludedId, string $ip): bool;

    /**
     * Determine that user with the same ip is not authenticated.
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
     * @return mixed
     */
    public function list(array $columns = ['*']);

    /**
     * Retrieve a list of the existing users excluding given user id.
     *
     * @return mixed
     */
    public function exclusiveList(string $id, array $columns = ['*']);

    /**
     * Iterate throw the existing users using a cursor.
     *
     * @param \Closure $scope
     */
    public function cursor(?\Closure $scope = null): LazyCollection;

    /**
     * Search over Collaboration Users.
     *
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Collaboration Users query.
     */
    public function userQuery(): Builder;

    /**
     * Activate specified Collaboration User.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate specified Collaboration User.
     */
    public function deactivate(string $id): bool;

    /**
     * Delete specified Collaboration User.
     */
    public function delete(string $id): bool;

    /**
     * Retrieve All Users who have Administrator role.
     */
    public function administrators(): DbCollection;

    /**
     * Reset Password for specified User.
     */
    public function resetPassword(StoreResetPasswordRequest $request, string $id): bool;

    /**
     * Reset specified User (set already_logged_in to 0).
     */
    public function resetAccount(string $id): bool;

    /**
     * Perform Intitiated Password Reset.
     */
    public function performResetPassword(PasswordResetRequest $request, string $token): bool;

    /**
     * Verify the specified PasswordReset Token.
     */
    public function verifyPasswordReset(string $token): bool;

    /**
     * Get users have specific permission.
     *
     * @return mixed
     */
    public function getUsersWithPermission(string $permission);

    /**
     * Sync users permissions with the given.
     * Given permission will be granted to passed users and revoked from users which are not passed.
     */
    public function syncUsersPermission(array $ids, string $permission): array;

    /**
     * Give specific permission to user.
     */
    public function givePermissionTo(string $permissionName, User $user): bool;

    /**
     * Revoke specific permission from user.
     */
    public function revokePermissionTo(string $permissionName, User $user): bool;

    /**
     * Map Resource into UserRepositoryCollection.
     *
     * @param mixed $resource
     */
    public function toCollection($resource): UserRepositoryCollection;
}
