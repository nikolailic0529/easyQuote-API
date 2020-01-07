<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\{
    System\ActivityRepositoryInterface as ActivityRepository,
    UserRepositoryInterface as UserRepository
};
use App\Http\Requests\System\GetActivitiesRequest;
use App\Models\System\Activity;
use App\Models\User;

class ActivityController extends Controller
{
    protected $activity;

    protected $user;

    public function __construct(ActivityRepository $activity, UserRepository $user)
    {
        $this->activity = $activity;
        $this->user = $user;
        $this->authorizeResource(Activity::class, 'activity');
    }

    /**
     * Display a listing of the Activities.
     *
     * @param \App\Http\Requests\System\GetActivitiesRequest $request
     * @return \Illuminate\Http\Response
     */
    public function index(GetActivitiesRequest $request)
    {
        return response()->json(
            request()->filled('search')
                ? $this->activity->search(request('search'))
                : $this->activity->all()
        );
    }

    /**
     * Display a listing of the Activities in the specified Subject.
     *
     * @param \App\Http\Requests\System\GetActivitiesRequest $request
     * @return \Illuminate\Http\Response
     */
    public function subject(GetActivitiesRequest $request, string $subject)
    {
        $this->authorize('viewAny', Activity::class);

        return response()->json(
            request()->filled('search')
                ? $this->activity->searchSubjectActivities($subject, request('search'))
                : $this->activity->subjectActivities($subject)
        );
    }

    /**
     * Export a listing of the Activities in specified format.
     *
     * @param \App\Http\Requests\System\GetActivitiesRequest $request
     * @param string $type
     * @return \Illuminate\Http\Response
     */
    public function export(GetActivitiesRequest $request, string $type)
    {
        $this->authorize('viewAny', Activity::class);

        return response()->download(
            $this->activity->export($type)
        );
    }

    /**
     * Export a list of the Activities for specified subject in specified format.
     *
     * @param \App\Http\Requests\System\GetActivitiesRequest $request
     * @param string $subject
     * @param string $type
     * @return \Illuminate\Http\Response
     */
    public function exportSubject(GetActivitiesRequest $request, string $subject, string $type)
    {
        $this->authorize('viewAny', Activity::class);

        return response()->download(
            $this->activity->exportSubject($subject, $type)
        );
    }

    /**
     * Display the meta information for activities filtering.
     *
     * @return \Illuminate\Http\Response
     */
    public function meta()
    {
        $this->authorize('viewAny', Activity::class);

        $meta = $this->activity->meta();
        $users = $this->user->listWithTrashed();

        return response()->json(
            $meta + compact('users')
        );
    }
}
