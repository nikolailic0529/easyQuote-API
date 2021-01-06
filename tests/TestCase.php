<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
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
    protected function authenticateApi(?Authenticatable $user = null)
    {
        $user ??= factory(User::class)->create();

        $this->withoutMiddleware(\App\Http\Middleware\PerformUserActivity::class);
        
        $this->actingAs($user, 'api');

        return $this;
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
