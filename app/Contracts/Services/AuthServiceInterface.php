<?php

namespace App\Contracts\Services;

use App\Http\Requests\RefreshTokenRequest;
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
     * Store Access Attempt
     * If successfull will set relevant flag
     *
     * @param array $payload
     * @return void
     */
    public function storeAccessAttempt(array $payload);

    /**
     * Generate and return token for authenticated user
     *
     * @param array $attributes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function generateToken(array $attributes): PersonalAccessTokenResult;

    /**
     * Authenticate User
     *
     * @param UserSignInRequest|array $request
     * @return mixed
     */
    public function authenticate($request);

    /**
     * Response when Successful Authentication
     *
     * @param PersonalAccessTokenResult $token
     * @return array
     */
    public function response(PersonalAccessTokenResult $token): array;
}
