<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPipelineOrderPipelinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->unsignedBigInteger('pipeline_order')->default(0)->after('pipeline_name')->comment('Pipeline order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropColumn('pipeline_order');
        });
    }
}
