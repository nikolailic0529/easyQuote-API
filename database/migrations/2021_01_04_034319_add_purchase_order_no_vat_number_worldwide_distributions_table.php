<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseOrderNoVatNumberWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->string('purchase_order_number', 250)->nullable()->after('additional_notes')->comment('Purchase Order Number');
            $table->string('vat_number', 250)->nullable()->after('purchase_order_number')->comment('VAT Number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_order_number',
                'vat_number',
            ]);
        });
    }
}
