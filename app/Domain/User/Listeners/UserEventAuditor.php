<?php

namespace App\Domain\User\Listeners;

use App\Domain\Authorization\Events\RoleUpdated;
use App\Domain\User\Events\UserUpdated;
use App\Domain\User\Models\User;
use App\Foundation\Support\Elasticsearch\Jobs\IndexSearchableEntity;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

final class UserEventAuditor implements ShouldQueue
{
    public function __construct(
        protected readonly BusDispatcher $busDispatcher,
    ) {
    }

    public function subscribe(): array
    {
        return [
            UserUpdated::class => [
                [UserEventAuditor::class, 'indexUserOnUpdate'],
            ],
            RoleUpdated::class => [
                [UserEventAuditor::class, 'indexRelatedUsersOnRoleUpdate'],
            ],
        ];
    }

    public function indexUserOnUpdate(UserUpdated $event): void
    {
        $this->busDispatcher->dispatch(
            new IndexSearchableEntity($event->user)
        );
    }

    public function indexRelatedUsersOnRoleUpdate(RoleUpdated $event): void
    {
        $event->role->users()
            ->each(function (User $user): void {
                $this->busDispatcher->dispatch(
                    new IndexSearchableEntity($user)
                );
            });
    }

}
