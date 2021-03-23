<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryKeyQuoteTemplateTemplateFieldTable extends Migration
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

        $indexes = $schemaManager->listTableIndexes('quote_template_template_field');

        Schema::table('quote_template_template_field', function (Blueprint $table) use ($indexes) {

            $table->dropForeign(['quote_template_id']);
            $table->dropForeign(['template_field_id']);

            if (isset($indexes['primary'])) {
                $table->dropPrimary();
            }

            $table->primary(['quote_template_id', 'template_field_id'], 'quote_template_template_field_primary');

        });

        Schema::table('quote_template_template_field', function (Blueprint $table) {

            $table->foreign('quote_template_id')->references('id')->on('quote_templates')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('template_field_id')->references('id')->on('template_fields')->cascadeOnDelete()->cascadeOnUpdate();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_template_template_field', function (Blueprint $table) {

            $table->dropForeign(['quote_template_id']);
            $table->dropForeign(['template_field_id']);

            $table->dropPrimary();

            $table->primary(['quote_template_id', 'template_field_id'], 'quote_template_template_field_primary');

        });

        Schema::table('quote_template_template_field', function (Blueprint $table) {

            $table->foreign('quote_template_id')->references('id')->on('quote_templates')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('template_field_id')->references('id')->on('template_fields')->cascadeOnDelete()->cascadeOnUpdate();

        });
    }
}
