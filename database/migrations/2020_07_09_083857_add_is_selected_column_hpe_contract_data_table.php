<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSelectedColumnHpeContractDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contract_data', function (Blueprint $table) {
            $table->boolean('is_selected')->default(0)->index()->comment('Whether asset is selected');
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
            $table->dropIndex(['is_selected']);
            $table->dropColumn('is_selected');
        });
    }
}
