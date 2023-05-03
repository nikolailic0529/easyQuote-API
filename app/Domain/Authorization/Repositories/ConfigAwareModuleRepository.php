<?php

namespace App\Domain\Authorization\Repositories;

use App\Domain\Authorization\Contracts\ModuleRepository;
use App\Domain\Authorization\Repositories\Exceptions\ModuleNotFoundException;
use App\Domain\Authorization\Repositories\Models\Module;
use App\Domain\Authorization\Repositories\Models\Privilege;
use App\Domain\Authorization\Repositories\Models\SubModule;

final class ConfigAwareModuleRepository implements ModuleRepository
{
    public function __construct(
        protected readonly array $config,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name): Module
    {
        /** @var array<string, list<string>> $privileges */
        $privileges = $this->getModules()[$name] ?? null;

        if (!$privileges) {
            throw ModuleNotFoundException::name($name);
        }

        return new Module(
            name: $name,
            privileges: $this->mapPrivileges($privileges),
            subModules: $this->resolveSubmodulesOf($name),
        );
    }

    private function resolveSubmodulesOf(string $name): array
    {
        $submodules = $this->getSubmodules()[$name] ?? null;

        if (!$submodules) {
            return [];
        }

        return collect($submodules)
            ->map(function (array $privileges, string $name): SubModule {
                return new SubModule($name, $this->mapPrivileges($privileges));
            })
            ->all();
    }

    /**
     * @return list<Privilege>
     */
    private function mapPrivileges(array $privileges): array
    {
        return collect($privileges)
            ->map(static function (array $permissions, string $level): Privilege {
                return new Privilege(
                    level: $level,
                    permissions: $permissions,
                );
            })
            ->values()
            ->all();
    }

    private function getSubmodules(): array
    {
        return $this->config['submodules'];
    }

    private function getModules(): array
    {
        return $this->config['modules'];
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->getModules() as $name => $_) {
            yield $this->get($name);
        }
    }
}
