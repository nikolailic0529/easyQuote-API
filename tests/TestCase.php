<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Unit\Traits\{
    AssertsModelAttributes,
    WithClientCredentials,
    WithFakeQuote,
    WithFakeQuoteFile,
    WithFakeUser
};
use App\Models\User;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, AssertsModelAttributes;

    /**
     * Boot the testing helper traits.
     *
     * @return array
     */
    protected function setUpTraits()
    {
        $uses = parent::setUpTraits();

        if (isset($uses[WithClientCredentials::class])) {
            $this->setUpClientCredentials();
        }

        if (isset($uses[WithFakeUser::class])) {
            $this->setUpFakeUser();
        }

        if (isset($uses[WithFakeQuote::class]) && isset($uses[WithFakeUser::class])) {
            $this->setUpFakeQuote();
        }

        if (isset($uses[WithFakeQuoteFile::class])) {
            $this->setUpFakeQuoteFile();
        }

        return $uses;
    }

    /**
     * Authenticate as User on API guard.
     * Disable Activity Middleware.
     *
     * @param  Authenticatable|null $user
     * @return $this
     */
    protected function authenticateApi(?Authenticatable $user = null): static
    {
        return tap($this, function () use ($user) {
            $user ??= factory(User::class)->create();

            $this->withoutMiddleware(\App\Http\Middleware\PerformUserActivity::class);

            $this->actingAs($user, 'api');

            return $this;
        });
    }

    protected function authenticateAsClient(): static
    {
        return tap($this, function () {

            /** @var ClientRepository $clientRepository */
            $clientRepository = $this->app[ClientRepository::class];

            $appName = $this->app['config']['app.name'];
            $clientName = "$appName::test_client";

            /** @var Client $client */

            try {
                $client = Passport::client()->query()->where('name', $clientName)->sole();
            } catch (ModelNotFoundException) {
                $client = $clientRepository->create(userId: null, name: $clientName, redirect: '');
            }

            $response = $this->postJson('oauth/token', [
                'client_id' => $client->getKey(),
                'client_secret' => $client->secret,
                'grant_type' => 'client_credentials',
                'scope' => '*'
            ]);

            $this->withHeader('Authorization', "Bearer {$response->json('access_token')}");
        });
    }

    /**
     * Assert that the given associative array has equal values from the second one.
     *
     * @param array $assocArray
     * @param array $assocValues
     * @return void
     */
    protected function assertArrayHasEqualValues(array $assocArray, array $assocValues)
    {
        foreach ($assocValues as $key => $value) {
            $this->assertEquals($assocValues[$key] ?? null, $assocArray[$key] ?? null);
        }
    }
}
