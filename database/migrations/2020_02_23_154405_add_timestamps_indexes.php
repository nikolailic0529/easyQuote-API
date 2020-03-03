<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;

class AddTimestampsIndexes extends Migration
{
    protected array $indexableTimestamps = ['deleted_at', 'activated_at', 'expires_at', 'submitted_at'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $db = config('database.connections.mysql.database');
        $tables = collect(data_get(Schema::getAllTables(), "*.Tables_in_{$db}"));

        $tables->each(function ($table) {
            $columns = Schema::getColumnListing($table);
            $timestamps = collect(array_intersect($this->indexableTimestamps, $columns));

            Schema::table(
                $table, fn (Blueprint $table) => $timestamps->each(fn ($timestamp) => $table->index($timestamp))
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $db = config('database.connections.mysql.database');
        $tables = collect(data_get(Schema::getAllTables(), "*.Tables_in_{$db}"));

        $tables->each(function ($table) {
            $columns = Schema::getColumnListing($table);
            $timestamps = collect(array_intersect($this->indexableTimestamps, $columns));

            Schema::table(
                $table, fn (Blueprint $table) => $timestamps->each(fn ($timestamp) => $table->dropIndex([$timestamp]))
            );
        });
    }
}
