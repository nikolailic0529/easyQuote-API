<?php

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropUnusedIndexesQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Type::hasType('char')) {
            Type::addType('char', StringType::class);
        }

        /** @var \Doctrine\DBAL\Schema\DB2SchemaManager */
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();

        $indexes = collect(array_keys($schemaManager->listTableIndexes('quotes')));

        DB::transaction(
            fn () => DB::update("UPDATE `quotes` SET `document_type` = CASE WHEN `document_type` = 'contract' THEN ? ELSE ? END", [2, 1])
        );

        Schema::table('quotes', function (Blueprint $table) use ($indexes) {
            $indexes->contains('quotes_list_index') && $table->dropIndex('quotes_list_index');
            $indexes->contains('quotes_id_document_type_index') && $table->dropIndex('quotes_id_document_type_index');

            $table->char('document_type', 1)->change()->comment('Determines whether it quote or contract');

            !$indexes->contains('quotes_document_type_index') && $table->index('document_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
