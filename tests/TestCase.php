<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Unit\Traits\{
    AssertsModelAttributes,
    TruncatesDatabaseTables,
    WithClientCredentials,
    WithFakeQuote,
    WithFakeQuoteFile,
    WithFakeUser
};

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

        if (isset($uses[TruncatesDatabaseTables::class])) {
            $this->truncateDatabaseTables();
        }

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
}
