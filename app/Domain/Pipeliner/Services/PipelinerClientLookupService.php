<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerClientIntegration;
use App\Domain\Pipeliner\Integration\Models\ClientEntity;
use App\Domain\Pipeliner\Integration\Models\ClientFilterInput;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Services\Exceptions\MultiplePipelinerEntitiesFoundException;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Config\Repository as Config;

class PipelinerClientLookupService
{
    public function __construct(protected PipelinerClientIntegration $integration,
                                protected Config $config)
    {
    }

    public function find(User $user): ?ClientEntity
    {
        $entities = $this->integration->getByCriteria(
            filter: ClientFilterInput::new()
                ->email(EntityFilterStringField::ieq((string) $user->email))
        );

        if (empty($entities)) {
            return null;
        }

        if (count($entities) > 1) {
            throw new MultiplePipelinerEntitiesFoundException();
        }

        return array_shift($entities);
    }

    public function findDefaultEntity(): ?ClientEntity
    {
        $defaultClientEmail = $this->config->get('pipeliner.sync.default_client_email');

        if (blank($defaultClientEmail)) {
            throw new \InvalidArgumentException('Default client email must be defined.');
        }

        $entities = $this->integration->getByCriteria(
            filter: ClientFilterInput::new()
                ->email(EntityFilterStringField::ieq((string) $defaultClientEmail))
        );

        if (empty($entities)) {
            return null;
        }

        if (count($entities) > 1) {
            throw new MultiplePipelinerEntitiesFoundException();
        }

        return array_shift($entities);
    }
}
