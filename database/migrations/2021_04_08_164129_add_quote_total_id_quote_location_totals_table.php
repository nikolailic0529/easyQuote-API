<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteTotalIdQuoteLocationTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('quote_location_totals')->delete();

        Schema::table('quote_location_totals', function (Blueprint $table) {
            $table->foreignUuid('quote_total_id')->after('id')->comment('Foreign key on quote_totals table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_location_totals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quote_total_id');
        });
    }
}
