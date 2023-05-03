<?php

namespace App\Domain\Authorization\Listeners;

use App\Domain\Activity\Services\ActivityLogger;
use App\Domain\Activity\Services\ChangesDetector;
use App\Domain\Authorization\Events\RoleCreated;
use App\Domain\Authorization\Events\RoleDeleted;
use App\Domain\Authorization\Events\RoleUpdated;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Services\RolePresenter;
use Illuminate\Contracts\Queue\ShouldQueue;

final class RoleEventAuditor implements ShouldQueue
{
    public function __construct(
        protected readonly ActivityLogger $activityLogger,
        protected readonly ChangesDetector $changesDetector,
        protected readonly RolePresenter $rolePresenter,
    ) {
    }

    public function subscribe(): array
    {
        return [
            RoleCreated::class => [
                [RoleEventAuditor::class, 'auditCreatedEvent'],
            ],
            RoleUpdated::class => [
                [RoleEventAuditor::class, 'auditUpdatedEvent'],
            ],
            RoleDeleted::class => [
                [RoleEventAuditor::class, 'auditDeletedEvent'],
            ],
        ];
    }

    public function auditCreatedEvent(RoleCreated $event): void
    {
        $this->activityLogger
            ->on($event->role)
            ->by($event->causer)
            ->withProperty(ChangesDetector::NEW_ATTRS_KEY, $this->getAttributesToBeLogged($event->role))
            ->log('created');
    }

    public function auditUpdatedEvent(RoleUpdated $event): void
    {
        $this->activityLogger
            ->on($event->role)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    oldAttributeValues: $this->getAttributesToBeLogged($event->oldRole),
                    newAttributeValues: $this->getAttributesToBeLogged($event->role)
                )
            )
            ->submitEmptyLogs(false)
            ->log('updated');
    }

    public function auditDeletedEvent(RoleDeleted $event): void
    {
        $this->activityLogger
            ->on($event->role)
            ->by($event->causer)
            ->log('deleted');
    }

    private function getAttributesToBeLogged(Role $role): array
    {
        return [
            'name' => $role->name,
            'modules_privileges' => collect($this->rolePresenter->presentModules($role))
                ->map(static function (array $module) {
                    $name = $module['module'];
                    $privilege = $module['privilege'];

                    return "$name [$privilege]";
                })
                ->join(', '),

        ];
    }
}
