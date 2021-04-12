<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsOpportunityStartDateAssumedIsOpportunityEndDateAssumedColumnsOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->boolean('is_opportunity_start_date_assumed')->default(false)->after('opportunity_start_date')->comment('Whether the opportunity start date is assumed');
            $table->boolean('is_opportunity_end_date_assumed')->default(false)->after('opportunity_end_date')->comment('Whether the opportunity start date is assumed');
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
                'is_opportunity_start_date_assumed',
                'is_opportunity_end_date_assumed',
            ]);
        });
    }
}
