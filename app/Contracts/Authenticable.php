<?php

namespace App\Contracts;

use Illuminate\Http\Request;
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
     * @return json response
     */
    public function logout(Request $request);

    /**
     * get current user data
     * @return json response
     */
    public function user(Request $request);

    /**
     * set token after successfull authorization
     * @return void
     */
    public function setToken(UserSignInRequest $request);
}