<?php

namespace App\Domain\Authentication\Contracts;

use App\Domain\User\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\PersonalAccessTokenResult;

interface AuthServiceInterface
{
    /**
     * Try to authenticate user.
     *
     * @return void
     */
    public function mustValidateCredentials(Authenticatable $user, array $credentials): void;

    /**
     * Generate and return token for authenticated user.
     */
    public function generateToken(Authenticatable $user): PersonalAccessTokenResult;

    /**
     * Authenticate User and generate the Personal Access Token.
     * Mark User as Logged In.
     *
     * @return mixed
     */
    public function authenticate(array $credentials);

    /**
     * Revoke the Personal Access Token of the Current Authenticated User.
     * Mark User as Logged Out.
     *
     * @param \App\Domain\User\Models\User $user
     *
     * @return void
     */
    public function logout(?User $user = null);

    /**
     * Response when Successful Authentication.
     */
    public function tokenToResponse(PersonalAccessTokenResult $token): array;
}
