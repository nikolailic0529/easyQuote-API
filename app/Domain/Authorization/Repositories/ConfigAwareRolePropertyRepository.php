<?php

namespace App\Domain\Authorization\Repositories;

use App\Domain\Authorization\Contracts\RolePropertyRepository;

final class ConfigAwareRolePropertyRepository implements RolePropertyRepository
{
    public function __construct(
        protected readonly array $config,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->getProperties() as $property) {
            yield $property;
        }
    }

    private function getProperties(): array
    {
        return $this->config['properties'];
    }
}
