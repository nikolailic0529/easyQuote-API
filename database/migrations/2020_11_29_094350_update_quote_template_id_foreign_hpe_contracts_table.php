<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQuoteTemplateIdForeignHpeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->dropForeign(['quote_template_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->foreign('quote_template_id')->references('id')->on('hpe_contract_templates')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }
}
