<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\Models\ClientEntity;
use App\Models\Data\Timezone;
use App\Models\User;
use Webpatser\Uuid\Uuid;

class PipelinerClientEntityToUserProjector
{
    public function __construct(protected ClientEntity $entity)
    {
    }

    public static function from(ClientEntity $entity): static
    {
        return new static($entity);
    }

    public function __invoke(): User
    {
        /** @var User|null $user */
        $user = User::query()->where('pl_reference', $this->entity->id)->first();

        return $user ?? tap(User::query()->where('email', $this->entity->email)->firstOrNew(), function (User $user): void {
                if (false === $user->exists) {
                    $user->{$user->getKeyName()} = (string)Uuid::generate(4);

                    $user->timezone()->associate(Timezone::query()->where('abbr', 'UTC')->first());
                }

                $user->pl_reference = $this->entity->id;
                $user->first_name = $this->entity->firstName;
                $user->last_name = $this->entity->lastName;
                $user->email = $this->entity->email;
            });
    }
}