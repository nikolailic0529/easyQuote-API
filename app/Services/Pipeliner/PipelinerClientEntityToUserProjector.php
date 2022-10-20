<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\Models\ClientEntity;
use App\Models\Data\Timezone;
use App\Models\User;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PipelinerClientEntityToUserProjector
{
    const TIMEZONE_ABBR = 'UTC';
    const TEAM_ID = UT_EPD_WW;

    public function __construct(
        protected readonly Cache $cache,
        protected readonly LockProvider $lockProvider,
    ) {
    }

    public function __invoke(ClientEntity $entity): User
    {
        $userId = $this->cache->get($this->getCacheKeyForEntity($entity));

        if (null !== $userId) {
            try {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return User::query()->findOrFail($userId);
            } catch (ModelNotFoundException) {
                $this->cache->forget($this->getCacheKeyForEntity($entity));
            }
        }

        return $this->lockProvider->lock($this->getLockNameForEntity($entity), 30)
            ->block(30, function () use ($entity): User {
                $user = $this->tryFindUser($entity) ?? new User();

                $this->mapUser($user, $entity)->save();

                return tap($user, function (User $user) use ($entity): void {
                    $this->cache->add($this->getCacheKeyForEntity($entity), $user->getKey(), now()->addHours(8));
                });
            });
    }

    private function getCacheKeyForEntity(ClientEntity $entity): string
    {
        return static::class.$entity->id;
    }

    private function getLockNameForEntity(ClientEntity $entity): string
    {
        return static::class.':lock'.$entity->id;
    }

    private function tryFindUser(ClientEntity $entity): ?User
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return User::query()->where('pl_reference', $entity->id)->first()
            ?? User::query()->where('email', $entity->email)->first();
    }

    private function mapUser(User $user, ClientEntity $entity): User
    {
        return tap($user, function (User $user) use ($entity): void {
            if (false === $user->exists) {
                $user->setId();
                $user->timezone()->associate(Timezone::query()->where('abbr', self::TIMEZONE_ABBR)->first());
                $user->team()->associate(self::TEAM_ID);
                $user->first_name = $entity->firstName;
                $user->last_name = $entity->lastName;
                $user->email = $entity->email;
            }

            $user->pl_reference = $entity->id;
        });
    }
}