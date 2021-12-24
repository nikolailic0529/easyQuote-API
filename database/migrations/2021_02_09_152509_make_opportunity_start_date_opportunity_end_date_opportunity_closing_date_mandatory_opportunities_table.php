<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeOpportunityStartDateOpportunityEndDateOpportunityClosingDateMandatoryOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->date('opportunity_start_date')->nullable(false)->change();
            $table->date('opportunity_end_date')->nullable(false)->change();
            $table->date('opportunity_closing_date')->nullable(false)->change();
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
            $table->date('opportunity_start_date')->nullable(true)->change();
            $table->date('opportunity_end_date')->nullable(true)->change();
            $table->date('opportunity_closing_date')->nullable(true)->change();
        });
    }
}
