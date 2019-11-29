<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\System\ActivityRepositoryInterface as ActivityRepository;
use App\Models\System\Activity;

class ActivityController extends Controller
{
    protected $activity;

    public function __construct(ActivityRepository $activity)
    {
        $this->activity = $activity;
        $this->authorizeResource(Activity::class, 'activity');
    }

    /**
     * Display a listing of the Activities.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
     * @param string $subject
     * @return void
     */
    public function subject(string $subject)
    {
        return response()->json(
            request()->filled('search')
                ? $this->activity->searchSubjectActivities($subject, request('search'))
                : $this->activity->subjectActivities($subject)
        );
    }
}
