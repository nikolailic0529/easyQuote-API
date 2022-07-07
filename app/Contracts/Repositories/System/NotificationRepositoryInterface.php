<?php

namespace App\Contracts\Repositories\System;

use App\Http\Resources\V1\NotificationCollection;
use App\Models\System\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /**
     * User's Eloquent Notification query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Regular Eloquent Notification query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(): Builder;

    /**
     * Retrieve a specified Notification.
     *
     * @param string $id
     * @return \App\Models\System\Notification|null
     */
    public function find(string $id);

    /**
     * Create a new Notification.
     *
     * @param array $attributes
     * @return \App\Models\System\Notification
     */
    public function create(array $attributes): Notification;

    /**
     * Make a new Notification instance.
     *
     * @param array $attributes
     * @return \App\Models\System\Notification
     */
    public function make(array $attributes = []): Notification;

    /**
     * Retrieve all the existing Notifications.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(): Collection;

    /**
     * Retrieve the latest existing limited Notifications.
     *
     * @param \App\Models\User|null $user
     * @return mixed
     */
    public function latest(?User $user = null);

    /**
     * Search over the exiting Notifications.
     *
     * @param string $query
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
     * @param \App\Models\System\Notification|string $notification
     * @return boolean
     */
    public function delete($notification): bool;

    /**
     * Read a specified Notification.
     *
     * @param \App\Models\System\Notification|string $notification
     * @return boolean
     */
    public function read($notification): bool;

    /**
     * Delete all the existing Notifications for specified User.
     *
     * @param \App\Models\User|null $user
     * @return boolean
     */
    public function deleteAll(?User $user = null): bool;

    /**
     * Read all the existing Notifications for specified User.
     *
     * @param \App\Models\User|null $user
     * @return boolean
     */
    public function readAll(?User $user = null): bool;

    /**
     * Transforms resource to Collection.
     *
     * @param mixed $resource
     * @return \App\Http\Resources\V1\NotificationCollection
     */
    public function toCollection($resource): NotificationCollection;
}
