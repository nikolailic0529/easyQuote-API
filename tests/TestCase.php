<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        parent::tearDown();

        gc_collect_cycles();
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
            $user ??= User::factory()->create();

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

    protected function pruneUsing(Builder $query, int $chunkSize = 100): void
    {
        do {
            $count = $query->clone()->take($chunkSize)->delete();
        } while ($count > 0);
    }

    /**
     * Assert that the given associative array has equal values from the second one.
     *
     * @param array $assocArray
     * @param array $assocValues
     * @return void
     */
    protected function assertArrayHasEqualValues(array $assocArray, array $assocValues): void
    {
        foreach ($assocValues as $key => $value) {
            $this->assertEquals($value, $assocArray[$key] ?? null);
        }
    }
}
