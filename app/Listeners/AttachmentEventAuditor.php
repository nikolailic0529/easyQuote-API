<?php

namespace App\Listeners;

use App\Events\Attachment\AttachmentCreated;
use App\Events\Attachment\AttachmentDeleted;
use App\Events\Attachment\AttachmentExported;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Contracts\Events\Dispatcher;

class AttachmentEventAuditor
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(protected ActivityLogger $activityLogger)
    {
    }


    public function subscribe(Dispatcher $events)
    {
        $events->listen(AttachmentCreated::class, [self::class, 'handleCreateEvent']);
        $events->listen(AttachmentDeleted::class, [self::class, 'handleDeleteEvent']);
        $events->listen(AttachmentExported::class, [self::class, 'handleExportEvent']);
    }

    public function handleCreateEvent(AttachmentCreated $event)
    {
        $attachment = $event->getAttachment();
        $parentEntity = $event->getParentEntity();

        $this->activityLogger
            ->performedOn($parentEntity)
            ->by($event->getCauser())
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [
                    'attachments' => $parentEntity->attachments()->oldest()->whereKeyNot($attachment->getKey())->pluck('filename')->implode(', '),
                ],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'attachments' => $parentEntity->attachments()->oldest()->pluck('filename')->implode(', '),
                ],
            ])
            ->log('updated');

    }

    public function handleExportEvent(AttachmentExported $event)
    {
        $attachment = $event->getAttachment();
        $parentEntity = $event->getParentEntity();

        $this->activityLogger
            ->performedOn($parentEntity)
            ->by($event->getCauser())
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'downloaded_attachment' => $attachment->filename,
                ],
            ])
            ->log('exported');
    }

    public function handleDeleteEvent(AttachmentDeleted $event)
    {
        $attachment = $event->getAttachment();
        $parentEntity = $event->getParentEntity();

        $this->activityLogger
            ->performedOn($parentEntity)
            ->by($event->getCauser())
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [
                    'attachments' => $parentEntity->attachments()->oldest()->pluck('filename')->implode(', '),
                ],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'attachments' => $parentEntity->attachments()->oldest()->whereKeyNot($attachment->getKey())->pluck('filename')->implode(', '),
                ],
            ])
            ->log('updated');
    }
}
