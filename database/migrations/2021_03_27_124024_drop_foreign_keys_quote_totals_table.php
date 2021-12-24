<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropForeignKeysQuoteTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_totals', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);

            $table->dropForeign(['customer_id']);
        });

        $schemaManager = DB::getDoctrineConnection()->getSchemaManager();

        $tableIndexes = $schemaManager->listTableIndexes('quote_totals');

        Schema::table('quote_totals', function (Blueprint $table) use ($tableIndexes) {
            if (isset($tableIndexes['quote_totals_quote_id_foreign'])) {
                $table->dropIndex('quote_totals_quote_id_foreign');
            }

            if (isset($tableIndexes['quote_totals_customer_id_foreign'])) {
                $table->dropIndex('quote_totals_customer_id_foreign');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_totals', function (Blueprint $table) {
            $table->foreign('quote_id')->references('id')->on('quotes')->cascadeOnDelete();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
