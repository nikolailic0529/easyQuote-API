<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropQuoteTotalIdCustomerTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_totals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quote_total_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('customer_totals')->delete();

        Schema::table('customer_totals', function (Blueprint $table) {
            $table->foreignUuid('quote_total_id')->after('id')->comment('Foreign key on quote_totals table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
