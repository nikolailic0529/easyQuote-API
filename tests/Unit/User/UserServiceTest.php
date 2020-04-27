<?php

namespace Tests\Unit\User;

use App\Console\Commands\Routine\Notifications\PasswordExpiration;
use App\Models\User;
use App\Notifications\PasswordExpiration as PwdExpiredNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    /**
     * Test user password expiry notification.
     *
     * @return void
     */
    public function testPasswordExpiryNotification()
    {
        Notification::fake();

        $interval = ENF_PWD_CHANGE_DAYS - setting('password_expiry_notification');

        $user = tap(
            factory(User::class)->create(),
            fn (User $user) => $user->forceFill(['password_changed_at' => now()->subDays($interval)])->save()
        );

        Artisan::call(PasswordExpiration::class);

        Notification::assertSentTo($user, PwdExpiredNotification::class);

        /**
         * Assert that middleware enforces change the password after expiration.
         */

        $user->forceFill(['password_changed_at' => now()->subDays(ENF_PWD_CHANGE_DAYS)])->save();

        $this->assertTrue($user->mustChangePassword());

        $this->actingAs($user, 'api');

        $this->getJson(url('api/notifications'))
            ->assertStatus(422)
            ->assertExactJson([
                'ErrorCode' => 'MCP_00',
                'ErrorDetails' => MCP_00,
                'message' => MCP_00
            ]);
    }
}