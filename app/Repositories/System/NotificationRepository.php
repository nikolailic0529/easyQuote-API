<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\System\NotificationRepositoryInterface;
use App\Http\Resources\NotificationCollection;
use App\Models\System\Notification;
use App\Models\User;
use App\Repositories\SearchableRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class NotificationRepository extends SearchableRepository implements NotificationRepositoryInterface
{
    /** @var \App\Models\System\Notification */
    protected $notification;

    /** @var array */
    protected $attributes = [];

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

    public function latest()
    {
        $total = $this->userQuery()->count();
        $resource = $this->userQuery()->latest()->limit(5)->get();
        $data = $this->toCollection($resource);

        return compact('data', 'total');
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
        if (is_string($notification)) {
            return optional($this->find($notification))->delete() ?? false;
        }

        throw_unless($notification instanceof Notification, new \InvalidArgumentException(INV_ARG_NPK_01));

        return $notification->delete();
    }

    public function deleteAll(?User $user = null): bool
    {
        return $this->userQuery($user)->delete();
    }

    public function toCollection($resource): NotificationCollection
    {
        return NotificationCollection::make($resource);
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByPriority::class,
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
            'message^5', 'created_at^2'
        ];
    }
}
