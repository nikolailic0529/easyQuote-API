<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryKeyCountryQuoteTemplateTable extends Migration
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

        $indexes = $schemaManager->listTableIndexes('country_quote_template');

        Schema::table('country_quote_template', function (Blueprint $table) use ($indexes) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['quote_template_id']);

            if (isset($indexes['primary'])) {
                $table->dropPrimary();
            }

            $table->primary(['country_id', 'quote_template_id']);
        });

        Schema::table('country_quote_template', function (Blueprint $table) {
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('quote_template_id')->references('id')->on('quote_templates')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('country_quote_template', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['quote_template_id']);

            $table->dropPrimary();

            $table->primary(['country_id', 'quote_template_id']);
        });

        Schema::table('country_quote_template', function (Blueprint $table) {
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('quote_template_id')->references('id')->on('quote_templates')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
