<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusStatusReasonColumnsOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->mediumInteger('status')->after('notes')->default(1)->comment('Opportunity Status');
            $table->string('status_reason', 500)->nullable()->after('status')->comment('Opportunity Status Reason');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'status_reason'
            ]);
        });
    }
}
