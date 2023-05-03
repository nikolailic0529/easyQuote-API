<?php

namespace Tests\Unit\User;

use App\Domain\User\Middleware\EnforceChangePassword;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

/**
 * @group build
 */
class UserPasswordExpirationTest extends TestCase
{
    use DatabaseTransactions;

    public function testMiddlewareDoesntAllowToPassWithExpiredPassword(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        $this->travelTo(now()->subDays(config('user.password_expiration.days')));

        $user->forceFill(['password_changed_at' => now()->subDays(config('user.password_expiration.days'))])->save();

        $this->assertTrue($user->mustChangePassword());

        $request = new Request();
        $request->setUserResolver(static function () use ($user): User {
            return $user;
        });

        $middleware = $this->app->make(EnforceChangePassword::class);

        $this->expectException(HttpResponseException::class);

        $middleware->handle($request, static function (): void {
        });
    }

    public function testMiddlewareAllowsToPassWithExpiredPasswordToCertainRoutes(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        $this->travelTo(now()->subDays(config('user.password_expiration.days')));

        $user->forceFill(['password_changed_at' => now()->subDays(config('user.password_expiration.days'))])->save();

        $this->assertTrue($user->mustChangePassword());

        $request = new Request();
        $request->setUserResolver(static function () use ($user): User {
            return $user;
        });
        $request->setRouteResolver(static function (): Route {
            return new Route('GET', 'auth/user', ['as' => 'account.show']);
        });

        $middleware = $this->app->make(EnforceChangePassword::class);

        $middleware->handle($request, static function (): void {
        });
    }

    public function testMiddlewareAllowsToPassWithExpiredPasswordWhenDisabled(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        $this->travelTo(now()->subDays(config('user.password_expiration.days')));

        $user->forceFill(['password_changed_at' => now()->subDays(config('user.password_expiration.days'))])->save();

        $this->assertTrue($user->mustChangePassword());

        $request = new Request();
        $request->setUserResolver(static function () use ($user): User {
            return $user;
        });

        $this->app['config']['user'] = [
            ...$this->app['config']['user'], ...[
                'password_expiration' => [
                    'days' => 0,
                    'enabled' => false,
                ],
            ],
        ];

        $middleware = $this->app->make(EnforceChangePassword::class);

        $middleware->handle($request, static function (): void {
        });
    }
}
