<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHpeContractDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contract_data', function (Blueprint $table) {
            $table->renameColumn('customer_country_code', 'customer_state_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hpe_contract_data', function (Blueprint $table) {
            $table->renameColumn('customer_state_code', 'customer_country_code');
        });
    }
}
