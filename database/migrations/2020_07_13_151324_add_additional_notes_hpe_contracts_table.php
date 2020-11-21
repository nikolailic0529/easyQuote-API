<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalNotesHpeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->text('additional_notes')->nullable()->after('purchase_order_date')->comment('HPE Contract Additional Notes');
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
            $table->dropColumn('additional_notes');
        });
    }
}
