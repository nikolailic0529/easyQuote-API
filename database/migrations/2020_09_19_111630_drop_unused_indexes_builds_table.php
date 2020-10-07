<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUnusedIndexesBuildsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @var \Doctrine\DBAL\Schema\DB2SchemaManager */
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();

        $indexes = collect(array_keys($schemaManager->listTableIndexes('builds')));

        if ($indexes->contains('builds_id_git_tag_build_number_index')) {
            Schema::table('builds', function (Blueprint $table) {
                $table->dropIndex(['id', 'git_tag', 'build_number']);
            });
        }

        if ($indexes->contains('builds_git_tag_build_number_index')) {
            Schema::table('builds', function (Blueprint $table) {
                $table->dropIndex(['git_tag', 'build_number']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 
    }
}
