<?php

namespace App\Contracts;

use App\Http\Requests\UserSignUpRequest;
use App\Http\Requests\UserSignInRequest;

interface Authenticable
{
    /**
     * @return json response
     */
    public function signup(UserSignUpRequest $request);

    /**
     * @return json response
     */
    public function signin(UserSignInRequest $request);

    /**
     * @return UserSignUpRequest $request
     */
    public static function handleSignUpRequest(UserSignUpRequest $request);
}