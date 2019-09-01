<?php

namespace App\Contracts\Services;

use App\Http\Requests \ {
    UserSignInRequest,
    UserSignUpRequest
};

interface AuthServiceInterface
{
    /**
     * Try to authenticate user
     *
     * @param Array $credentials
     * @return void
     */
    public function checkCredentials(Array $credentials);

    /**
     * Store Access Attempt
     * If successfull will set relevant flag
     *
     * @param Array $payload
     * @return void
     */
    public function storeAccessAttempt(Array $payload);
    
    /**
     * Parse and return time from token
     *
     * @param [type] $tokenResult
     * @return void
     */
    public function parseTokenTime($tokenResult);
    
    /**
     * Generate and return token for authenticated user
     *
     * @param UserSignInRequest $request
     * @return Object
     */
    public function generateToken(UserSignInRequest $request);

    /**
     * Handling User Sign In Request
     *
     * @param UserSignUpRequest $request
     * @return UserSignUpRequest
     */
    public static function handleSignUpRequest(UserSignUpRequest $request);
}
