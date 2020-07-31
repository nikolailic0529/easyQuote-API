<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServicesHpeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->json('sold_contact')->after('contract_numbers')->change();
            $table->json('bill_contact')->after('contract_numbers')->change();
            $table->json('hw_delivery_contact')->after('contract_numbers')->change();
            $table->json('sw_delivery_contact')->after('contract_numbers')->change();
            $table->json('pr_support_contact')->after('contract_numbers')->change();
            $table->json('entitled_party_contact')->after('contract_numbers')->change();
            $table->json('end_customer_contact')->after('contract_numbers')->change();

            $table->json('services')->nullable()->after('contract_numbers')->comment('Aggregated Services by Contract');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->dropColumn('services');
        });
    }
}
