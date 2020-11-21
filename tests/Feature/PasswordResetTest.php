<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Unit\Traits\WithFakeUser;
use App\Models\User;
use App\Notifications\PasswordResetRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

class PasswordResetTest extends TestCase
{
    use WithFakeUser;

    /**
     * Test a new password request.
     *
     * @return void
     */
    public function testPasswordResetRequest()
    {
        Notification::fake(PasswordResetRequest::class);

        /** @var User */
        $user = factory(User::class)->create();

        $this->patchJson('/api/users/reset-password/' . $user->getKey(), ['host' => $host = 'http://localhost'])->assertOk();

        Notification::assertSentTo($user, PasswordResetRequest::class, function (PasswordResetRequest $notification) use ($user, $host) {
            $this->assertObjectHasAttribute('passwordReset', $notification);

            $this->assertModelAttributes($notification->passwordReset, [
                'user_id' => $user->getKey(),
                'host' => $host,
            ]);

            $this->assertSame((string) Str::of($host)->finish('/')->append('reset/', $notification->passwordReset->token), $notification->passwordReset->url);

            return true;
        });
    }

    /**
     * Test a new password reset request validation.
     *
     * @return void
     */
    public function testPasswordResetValidation()
    {
        Notification::fake(PasswordResetRequest::class);

        /** @var User */
        $user = factory(User::class)->create();

        $this->patchJson('/api/users/reset-password/' . $user->getKey(), ['host' => $host = 'http://localhost'])->assertOk();
        
        Notification::assertSentTo($user, PasswordResetRequest::class, function (PasswordResetRequest $notification) use ($host) {
            $token = substr($notification->passwordReset->url, strlen($host.'/reset/'));

            $this->getJson('api/auth/reset-password/'.$token)->assertOk()->assertExactJson([true]);

            return true;
        });
    }
}
