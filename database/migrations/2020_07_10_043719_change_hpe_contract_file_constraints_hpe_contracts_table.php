<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeHpeContractFileConstraintsHpeContractsTable extends Migration
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
            $table->dropForeign(['hpe_contract_file_id']);

            $table->foreign('hpe_contract_file_id')->references('id')->on('hpe_contract_files')->onDelete('SET NULL')->onUpdate('cascade');
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
            $table->dropForeign(['hpe_contract_file_id']);

            $table->foreign('hpe_contract_file_id')->references('id')->on('hpe_contract_files')->onDelete('cascade')->onUpdate('cascade');
        });
        Schema::enableForeignKeyConstraints();
    }
}
