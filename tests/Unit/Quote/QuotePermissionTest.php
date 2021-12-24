<?php

namespace Tests\Unit\Quote;

use Tests\TestCase;
use App\Events\NotificationCreated;
use App\Models\{
    Role,
    User,
};
use App\Notifications\{
    GrantedQuoteAccess,
    RevokedQuoteAccess,
};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{
    Event,
    Notification,
};
use Tests\Unit\Traits\{
    WithFakeQuote,
    WithFakeUser,
};

class QuotePermissionTest extends TestCase
{
    use WithFakeQuote, WithFakeUser, DatabaseTransactions;

    /**
     * Test authorized users listing.
     *
     * @return void
     */
    public function testQuoteAuthorizedUsersListing()
    {
        $quote = $this->createQuote($this->user);

        $this->getJson(url('api/quotes/permissions/' . $quote->id))->assertOk();
    }

    /**
     * Test grant quote permissions.
     *
     * @return void
     */
    public function testGrantQuotePermissions()
    {
        $quote = $this->createQuote($this->user);

        Event::fake([
            NotificationCreated::class
        ]);

        Notification::fake();

        /** System default role which does not have access to all quotes. */
        $role = Role::whereName('Sales Manager')->first();

        $authorizableUsers = factory(User::class, 5)->create(['role_id' => $role->id]);

        $this->putJson(url('api/quotes/permissions/' . $quote->id), ['users' => $authorizableUsers->pluck('id')->toArray()])
            ->assertOk()
            ->assertExactJson([true]);

        Notification::assertSentTo($authorizableUsers, GrantedQuoteAccess::class);

        Event::assertDispatchedTimes(NotificationCreated::class, $authorizableUsers->count());
    }

    /**
     * Test revoke quote permissions from authorized users.
     *
     * @return void
     */
    public function testRevokeQuotePermissions()
    {
        $quote = $this->createQuote($this->user);
        /** System default role which does not have access to all quotes. */
        $role = Role::whereName('Sales Manager')->first();

        $authorizableUsers = factory(User::class, 5)->create(['role_id' => $role->id]);

        $permission = app('quote.state')->getQuotePermission($quote, ['read', 'update']);

        /** Grant quote permissions to newly created users. */
        app('user.repository')->syncUsersPermission($authorizableUsers->pluck('id')->toArray(), $permission);

        $authorizableUsers->each(fn (User $user) => $this->assertTrue($user->hasPermissionTo($permission)));

        Event::fake([
            NotificationCreated::class
        ]);

        Notification::fake();

        $unuathorizableUsers = $authorizableUsers->splice(0, 2);

        $this->putJson(url('api/quotes/permissions/' . $quote->id), ['users' => $authorizableUsers->pluck('id')->toArray()])
            ->assertOk()
            ->assertExactJson([true]);

        Notification::assertSentTo($unuathorizableUsers, RevokedQuoteAccess::class);

        Event::assertDispatchedTimes(NotificationCreated::class, $unuathorizableUsers->count());
    }
}
