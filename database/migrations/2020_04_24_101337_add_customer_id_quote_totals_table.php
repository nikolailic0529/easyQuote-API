<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerIdQuoteTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_totals', function (Blueprint $table) {
            $table->uuid('customer_id')->nullable()->after('quote_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('cascade')->onDelete('cascade');

            $table->string('customer_name')->index()->after('total_price');

            $table->dropIndex(['rfq_number']);
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
            $table->dropIndex(['customer_name']);
            $table->dropForeign(['customer_id']);

            $table->dropColumn('customer_name');

            $table->index('rfq_number');
        });
    }
}
