<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEndUserColumnsHpeContractDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contract_data', function (Blueprint $table) {
            $table->string('reseller_name')->nullable()->after('customer_state_code')->comment('Reseller Name');
            $table->string('reseller_address')->nullable()->after('reseller_name')->comment('Reseller Address');
            $table->string('reseller_city')->nullable()->after('reseller_address')->comment('Reseller City');
            $table->string('reseller_state')->nullable()->after('reseller_city')->comment('Reseller State');
            $table->string('reseller_post_code')->nullable()->after('reseller_state')->comment('Reseller Postal Code');
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
            $table->dropColumn([
                'reseller_name',
                'reseller_address',
                'reseller_city',
                'reseller_state',
                'reseller_post_code',
            ]);
        });
    }
}
