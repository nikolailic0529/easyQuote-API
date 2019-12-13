<?php

namespace App\Exceptions;

use App\Models\AccessAttempt as AppAccessAttempt;
use App\Notifications\AccessAttempt;
use App\Models\User;
use Exception;

class AlreadyAuthenticatedException extends Exception
{
    protected $user;

    protected $accessAttempt;

    public function __construct(User $user, AppAccessAttempt $accessAttempt)
    {
        $this->user = $user;
        $this->accessAttempt = $accessAttempt;
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        $this->user->notify(new AccessAttempt($this->accessAttempt));
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json(['message' => AU_00, 'code' => 'AU_00']);
    }
}
