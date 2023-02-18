<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('opportunities', 'order_in_pipeline_stage')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->dropColumn('order_in_pipeline_stage');
            });
        }

        Schema::table('opportunities', function (Blueprint $table) {
            $table->bigInteger('order_in_pipeline_stage')->nullable()->after('sale_action_name')->comment('Order of opportunity in pipeline stage');
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
            $table->dropColumn('order_in_pipeline_stage');
        });
    }
};
