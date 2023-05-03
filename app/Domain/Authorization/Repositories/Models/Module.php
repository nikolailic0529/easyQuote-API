<?php

namespace App\Domain\Authorization\Repositories\Models;

use App\Domain\Authorization\Repositories\Exceptions\PrivilegeNotFoundException;
use App\Domain\Authorization\Repositories\Exceptions\SubModuleNotFoundException;

final class Module
{
    /**
     * @param list<Privilege> $privileges
     * @param list<SubModule> $subModules
     */
    public function __construct(
        public readonly string $name,
        public readonly array $privileges,
        public readonly array $subModules,
    ) {
    }

    /**
     * @throws PrivilegeNotFoundException
     */
    public function getPrivilege(string $level): Privilege
    {
        foreach ($this->privileges as $privilege) {
            if ($privilege->level === $level) {
                return $privilege;
            }
        }

        throw PrivilegeNotFoundException::level($level);
    }

    /**
     * @throws SubModuleNotFoundException
     */
    public function getSubmodule(string $name): SubModule
    {
        foreach ($this->subModules as $submodule) {
            if ($submodule->name === $name) {
                return $submodule;
            }
        }

        throw SubModuleNotFoundException::name($name);
    }
}
