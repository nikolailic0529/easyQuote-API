<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\{
    System\NotificationRepositoryInterface as NotificationRepository
};
use App\Models\System\Notification;

class NotificationController extends Controller
{
    /** @var \App\Contracts\Repositories\System\NotificationRepositoryInterface */
    protected $notification;

    public function __construct(NotificationRepository $notification)
    {
        $this->notification = $notification;
        $this->authorizeResource(Notification::class, 'notification');
    }

    /**
     * Display a listing of the Notifications.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            $this->notification->paginate()
        );
    }

    /**
     * Display a listing of the 5 latest Notifications for the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function latest()
    {
        $this->authorize('viewAny', Notification::class);

        return response()->json(
            $this->notification->latest()
        );
    }

    /**
     * Remove the specified Notification from storage.
     *
     * @param  \App\Models\System\Notification $notification
     * @return \Illuminate\Http\Response
     */
    public function destroy(Notification $notification)
    {
        return response()->json(
            $this->notification->delete($notification)
        );
    }

    /**
     * Remove all the existing Notifications from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyAll()
    {
        $this->authorize('deleteAll', Notification::class);

        return response()->json(
            $this->notification->deleteAll(auth()->user())
        );
    }

    /**
     * Mark as read the specified Notification in storage.
     *
     * @param  \App\Models\System\Notification $notification
     * @return \Illuminate\Http\Response
     */
    public function read(Notification $notification)
    {
        return response()->json(
            $this->notification->read($notification)
        );
    }

    /**
     * Mark as read all the existing Notifications.
     *
     * @return \Illuminate\Http\Response
     */
    public function readAll()
    {
        return response()->json(
            $this->notification->readAll()
        );
    }
}
