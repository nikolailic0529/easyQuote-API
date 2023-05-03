<?php

namespace App\Domain\User\Middleware;

use App\Domain\Authentication\Services\AuthService;
use App\Domain\User\Models\User;
use App\Domain\User\Services\UserActivityService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PerformUserActivity
{
    public function __construct(protected UserActivityService $activityService,
                                protected AuthService $authService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $user = $request->user();

        if (false === $user instanceof User) {
            return $next($request);
        }

        /*
         * If the User hasn't recent activity his token will be revoked and User will be logged out.
         */
        if (false === $this->activityService->userHasRecentActivity($user)) {
            error_abort(LO_00, 'LO_00', 401);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        $user = $request->user();

        if (false === $user instanceof User) {
            return;
        }

        if (false === $this->activityService->userHasRecentActivity($user)) {
            $this->authService->logout($user);

            return;
        }

        $this->activityService->updateActivityTimeOfUser($user);
    }
}
