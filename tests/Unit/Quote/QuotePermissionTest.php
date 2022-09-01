<?php

namespace Tests\Unit\Quote;

use App\Events\NotificationCreated;
use App\Models\{Quote\Quote, Role, User};
use App\Notifications\{GrantedQuoteAccess, RevokedQuoteAccess,};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{Event, Notification,};
use Tests\TestCase;

class QuotePermissionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test authorized users listing.
     *
     * @return void
     */
    public function testQuoteAuthorizedUsersListing()
    {
        $this->authenticateApi();

        /** @noinspection PhpParamsInspection */
        $quote = Quote::factory()->for(auth()->user())->create();

        $this->getJson(url('api/quotes/permissions/'.$quote->id))->assertOk();
    }

    /**
     * Test grant quote permissions.
     *
     * @return void
     */
    public function testGrantQuotePermissions()
    {
        $this->authenticateApi();

        /** @noinspection PhpParamsInspection */
        $quote = Quote::factory()->for(auth()->user())->create();

        Event::fake([
            NotificationCreated::class,
        ]);

        Notification::fake();

        /** System default role which does not have access to all quotes. */
        $role = Role::whereName('Sales Manager')->first();

        $usersWithQuotePermissions = User::factory(5)->create();
        $usersWithQuotePermissions->each->syncRoles($role);

        $this->putJson(url('api/quotes/permissions/'.$quote->id),
            ['users' => $usersWithQuotePermissions->pluck('id')->toArray()])
            ->assertOk()
            ->assertExactJson([true]);

        Notification::assertSentTo($usersWithQuotePermissions, GrantedQuoteAccess::class);

        Event::assertDispatchedTimes(NotificationCreated::class, $usersWithQuotePermissions->count());
    }

    /**
     * Test revoke quote permissions from authorized users.
     *
     * @return void
     */
    public function testRevokeQuotePermissions()
    {
        $this->authenticateApi();

        /** @noinspection PhpParamsInspection */
        $quote = Quote::factory()->for(auth()->user())->create();

        /** System default role which does not have access to all quotes. */
        $role = Role::whereName('Sales Manager')->first();

        $usersWithQuotePermissions = User::factory(5)->create();
        $usersWithQuotePermissions->each->syncRoles($role);

        $permission = app('quote.state')->getQuotePermission($quote, ['read', 'update']);

        /** Grant quote permissions to newly created users. */
        app('user.repository')->syncUsersPermission($usersWithQuotePermissions->pluck('id')->toArray(), $permission);

        $usersWithQuotePermissions->each(fn(User $user) => $this->assertTrue($user->hasPermissionTo($permission)));

        Event::fake([
            NotificationCreated::class,
        ]);

        Notification::fake();

        $usersWithRevokedQuotePermissions = $usersWithQuotePermissions->splice(0, 2);

        $this->putJson(url('api/quotes/permissions/'.$quote->id),
            ['users' => $usersWithQuotePermissions->pluck('id')->toArray()])
            ->assertOk()
            ->assertExactJson([true]);

        Notification::assertSentTo($usersWithRevokedQuotePermissions, RevokedQuoteAccess::class);

        Event::assertDispatchedTimes(NotificationCreated::class, $usersWithRevokedQuotePermissions->count());
    }
}
