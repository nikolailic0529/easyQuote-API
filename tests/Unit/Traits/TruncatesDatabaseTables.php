<?php

namespace Tests\Unit\Traits;
use DB, Schema;

trait TruncatesDatabaseTables
{
    protected function truncateDatabaseTables(): void
    {
        if (!isset($this->truncatableTables)) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        collect($this->truncatableTables)
            ->each(function ($table) {
                DB::table($table)->truncate();
            });

        Schema::enableForeignKeyConstraints();
    }
}
