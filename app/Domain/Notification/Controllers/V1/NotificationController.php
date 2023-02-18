<?php

namespace App\Domain\Notification\Controllers\V1;

use App\Domain\Notification\Contracts\NotificationRepositoryInterface as NotificationRepository;
use App\Domain\Notification\Models\Notification;
use App\Foundation\Http\Controller;

class NotificationController extends Controller
{
    /** @var \App\Domain\Notification\Contracts\NotificationRepositoryInterface */
    protected $notification;

    public function __construct(NotificationRepository $notification)
    {
        $this->notification = $notification;
        $this->authorizeResource(\App\Domain\Notification\Models\Notification::class, 'notification');
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
        $this->authorize('viewAny', \App\Domain\Notification\Models\Notification::class);

        return response()->json(
            $this->notification->latest()
        );
    }

    /**
     * Remove the specified Notification from storage.
     *
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
        $this->authorize('deleteAll', \App\Domain\Notification\Models\Notification::class);

        return response()->json(
            $this->notification->deleteAll(auth()->user())
        );
    }

    /**
     * Mark as read the specified Notification in storage.
     *
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
