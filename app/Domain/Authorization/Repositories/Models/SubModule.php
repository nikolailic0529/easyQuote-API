<?php

namespace App\Domain\Authorization\Repositories\Models;

use App\Domain\Authorization\Repositories\Exceptions\PrivilegeNotFoundException;

final class SubModule
{
    /**
     * @param string          $name
     * @param list<Privilege> $privileges
     */
    public function __construct(
        public readonly string $name,
        public readonly array $privileges,
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
}
