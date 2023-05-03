<?php

namespace Tests\Unit\Traits;

use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;

trait WithFakeUser
{
    use WithFaker;

    /**
     * User instance.
     *
     * @var \App\Domain\User\Models\User
     */
    protected $user;

    /**
     * An Access Token.
     *
     * @var string
     */
    protected $accessToken;

    /**
     * Authorization Header for Request.
     *
     * @var array
     */
    protected $authorizationHeader;

    /**
     * Setup up the User instance.
     *
     * @return void
     */
    protected function setUpFakeUser()
    {
        $this->user = $this->createUser();
        $this->accessToken = $this->createAccessToken();
        $this->authorizationHeader = ['Authorization' => "Bearer {$this->accessToken}"];

        if (!isset($this->dontAuthenticate) || $this->dontAuthenticate !== true) {
            $this->authenticate();
        }
    }

    protected function authenticate(): self
    {
        return $this->be($this->user, 'api');
    }

    protected function createUser(): User
    {
        return User::factory()->create();
    }

    protected function createAccessToken(?User $user = null): string
    {
        $user ??= $this->user;

        return $user->createToken('Personal Access Token')->accessToken;
    }
}
