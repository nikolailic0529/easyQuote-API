<?php

namespace App\Contracts\Services;

use App\Models\User;
use App\Http\Requests\UserSignInRequest;
use Laravel\Passport\PersonalAccessTokenResult;

interface AuthServiceInterface
{
    /**
     * Try to authenticate user
     *
     * @param array $credentials
     * @return void
     */
    public function checkCredentials(array $credentials);

    /**
     * Disable ip checking.
     *
     * @return AuthServiceInterface
     */
    public function disableCheckIp(): AuthServiceInterface;

    /**
     * Generate and return token for authenticated user
     *
     * @param array $attributes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function generateToken(array $attributes): PersonalAccessTokenResult;

    /**
     * Authenticate User and generate the Personal Access Token.
     * Mark User as Logged In.
     *
     * @param array $request
     * @return mixed
     */
    public function authenticate(array $request);

    /**
     * Revoke the Personal Access Token of the Current Authenticated User.
     * Mark User as Logged Out.
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function logout(?User $user = null);

    /**
     * Response when Successful Authentication
     *
     * @param PersonalAccessTokenResult $token
     * @return array
     */
    public function response(PersonalAccessTokenResult $token): array;
}
