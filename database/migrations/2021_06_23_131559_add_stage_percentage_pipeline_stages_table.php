<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStagePercentagePipelineStagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->float('stage_percentage')->default(0.00)->after('stage_order')->comment('Pipeline stage percentage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn('stage_percentage');
        });
    }
}
