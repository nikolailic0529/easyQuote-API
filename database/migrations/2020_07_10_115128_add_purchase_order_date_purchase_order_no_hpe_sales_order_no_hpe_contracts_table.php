<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseOrderDatePurchaseOrderNoHpeSalesOrderNoHpeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->string('purchase_order_no')->nullable()->after('contract_number')->comment('Purchase Order Number');
            $table->string('hpe_sales_order_no')->nullable()->after('purchase_order_no')->comment('HPE Sales Order Number');

            $table->date('purchase_order_date')->nullable()->after('contract_date')->comment('Purchase Order Date');
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
            $table->dropColumn([
                'purchase_order_no',
                'hpe_sales_order_no',
                'purchase_order_date',
            ]);
        });
    }
}
