<?php

namespace App\Domain\Notification\Repositories;

use App\Domain\Notification\Contracts\NotificationRepositoryInterface;
use App\Domain\Notification\Events\NotificationDeletedAll;
use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Resources\V1\NotificationCollection;
use App\Domain\Shared\Eloquent\Repository\Concerns\ResolvesImplicitModel;
use App\Domain\User\Models\User;
use App\Repositories\{App\Foundation\Database\Eloquent\Repository\SearchableRepository};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class NotificationRepository extends \App\Domain\Shared\Eloquent\Repository\SearchableRepository implements NotificationRepositoryInterface
{
    use ResolvesImplicitModel;

    protected Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function query(): Builder
    {
        return $this->notification->query();
    }

    public function userQuery(?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        return $this->query()->where('notifications.user_id', optional($user)->id);
    }

    public function all(): Collection
    {
        return $this->userQuery()->get();
    }

    public function latest(?User $user = null)
    {
        $user = $user ?? auth()->user();

        $totals = $this->userQuery($user)
            ->selectRaw('count(*) as `total`')
            ->selectRaw('count(`read_at` or null) as `read`')
            ->selectRaw('count(case when `read_at` is null then 1 end) as `unread`')
            ->toBase()
            ->first();

        $limit = (int) $user->recent_notifications_limit;

        $resource = $this->userQuery($user)->latest()->whereNull('read_at')->limit($limit)->get();

        $data = $this->toCollection($resource);

        return compact('data') + (array) $totals;
    }

    public function paginate()
    {
        return $this->toCollection(parent::all());
    }

    public function find(string $id)
    {
        return $this->query()->whereId($id)->first();
    }

    public function create(array $attributes): Notification
    {
        return $this->notification->create($attributes);
    }

    public function make(array $attributes = []): Notification
    {
        return $this->notification->make($attributes);
    }

    public function delete($notification): bool
    {
        $notification = $this->resolveModel($notification);

        return $notification->delete();
    }

    public function read($notification): bool
    {
        $notification = $this->resolveModel($notification);

        return $notification->markAsRead();
    }

    public function deleteAll(?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        $deleted = $this->userQuery($user)->delete();

        event(new NotificationDeletedAll($user));

        return $deleted;
    }

    public function readAll(?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        $deleted = $this->userQuery($user)->update(['read_at' => now()]);

        event(new NotificationDeletedAll($user));

        return $deleted;
    }

    public function toCollection($resource): NotificationCollection
    {
        return NotificationCollection::make($resource);
    }

    public function model(): string
    {
        return Notification::class;
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Domain\Notification\Queries\Filters\OrderByCreatedAt::class,
            \App\Domain\Notification\Queries\Filters\OrderByPriority::class,
            \App\Foundation\Database\Eloquent\QueryFilter\DefaultOrderBy::class,
        ];
    }

    protected function filterableQuery()
    {
        return $this->userQuery();
    }

    protected function searchableModel(): Model
    {
        return $this->notification;
    }

    protected function searchableFields(): array
    {
        return [
            'message^5', 'created_at^2',
        ];
    }
}
