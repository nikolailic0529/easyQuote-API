<?php

namespace App\Exceptions;

use App\Notifications\AccessAttempt;
use App\Models\User;
use Exception;

class AlreadyAuthenticatedException extends Exception
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        $this->user->notify(new AccessAttempt);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json(['message' => __('auth.already_authenticated')]);
    }
}
