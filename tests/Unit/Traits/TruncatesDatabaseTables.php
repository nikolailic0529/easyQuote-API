<?php

namespace Tests\Unit\Traits;

trait TruncatesDatabaseTables
{
    protected function truncateDatabaseTables(): void
    {
        if (!isset($this->truncatableTables)) {
            return;
        }

        \Schema::disableForeignKeyConstraints();

        collect($this->truncatableTables)
            ->each(fn ($table) => \DB::table($table)->truncate());

        \Schema::enableForeignKeyConstraints();
    }
}
