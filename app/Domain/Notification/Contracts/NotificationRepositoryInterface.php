<?php

namespace App\Domain\Notification\Contracts;

use App\Domain\Notification\Resources\V1\NotificationCollection;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /**
     * User's Eloquent Notification query.
     */
    public function userQuery(): Builder;

    /**
     * Regular Eloquent Notification query.
     */
    public function query(): Builder;

    /**
     * Retrieve a specified Notification.
     *
     * @return \App\Domain\Notification\Models\Notification|null
     */
    public function find(string $id);

    /**
     * Create a new Notification.
     */
    public function create(array $attributes): \App\Domain\Notification\Models\Notification;

    /**
     * Make a new Notification instance.
     */
    public function make(array $attributes = []): \App\Domain\Notification\Models\Notification;

    /**
     * Retrieve all the existing Notifications.
     */
    public function all(): Collection;

    /**
     * Retrieve the latest existing limited Notifications.
     *
     * @return mixed
     */
    public function latest(?User $user = null);

    /**
     * Search over the exiting Notifications.
     *
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Paginate all the existing Notifications.
     *
     * @return mixed
     */
    public function paginate();

    /**
     * Delete a specified Notification.
     *
     * @param \App\Domain\Notification\Models\Notification|string $notification
     */
    public function delete($notification): bool;

    /**
     * Read a specified Notification.
     *
     * @param \App\Domain\Notification\Models\Notification|string $notification
     */
    public function read($notification): bool;

    /**
     * Delete all the existing Notifications for specified User.
     */
    public function deleteAll(?User $user = null): bool;

    /**
     * Read all the existing Notifications for specified User.
     */
    public function readAll(?User $user = null): bool;

    /**
     * Transforms resource to Collection.
     *
     * @param mixed $resource
     */
    public function toCollection($resource): NotificationCollection;
}
