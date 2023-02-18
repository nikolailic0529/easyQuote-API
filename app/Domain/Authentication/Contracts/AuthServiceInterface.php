<?php

namespace App\Domain\Authentication\Contracts;

use App\Domain\User\Models\User;
use Laravel\Passport\PersonalAccessTokenResult;

interface AuthServiceInterface
{
    /**
     * Try to authenticate user.
     *
     * @return void
     */
    public function checkCredentials(array $credentials);

    /**
     * Generate and return token for authenticated user.
     */
    public function generateToken(array $attributes): PersonalAccessTokenResult;

    /**
     * Authenticate User and generate the Personal Access Token.
     * Mark User as Logged In.
     *
     * @return mixed
     */
    public function authenticate(array $request);

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
    public function response(PersonalAccessTokenResult $token): array;
}
