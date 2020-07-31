<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateQuoteTemplatesTable extends Migration
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

        $indexes = collect(array_keys($schemaManager->listTableIndexes('quotes')));

        $indexes->contains('quote_templates_id_type_unique') && Schema::table('quote_templates', function (Blueprint $table) {
            $table->dropUnique(['id', 'type']);
        });

        DB::transaction(function () {
            DB::update("UPDATE `quote_templates` SET `type` = CASE WHEN `type` = ? THEN ? ELSE ? END", ['contract', QT_TYPE_CONTRACT, QT_TYPE_QUOTE]);
        });

        Schema::table('quote_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('type')->default(0)->nullable(false)->change();
        });
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
