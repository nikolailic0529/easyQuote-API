<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeOpportunityStartDateOpportunityEndDateNullableOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->date('opportunity_start_date')->nullable()->change();
            $table->date('opportunity_end_date')->nullable()->change();
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
            $table->date('opportunity_start_date')->nullable(false)->change();
            $table->date('opportunity_end_date')->nullable(false)->change();
        });
    }
}
