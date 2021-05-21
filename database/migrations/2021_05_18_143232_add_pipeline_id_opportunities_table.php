<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPipelineIdOpportunitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->uuid('pipeline_id')->nullable()->after('user_id')->comment('Foreign key on pipelines table');
        });

        DB::transaction(function () {

            DB::table('opportunities')
                ->update([
                    'pipeline_id' => PL_WWDP
                ]);

        });

        Schema::table('opportunities', function (Blueprint $table) {

            $table->uuid('pipeline_id')->nullable(false)->change();

        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreign('pipeline_id')->references('id')->on('pipelines')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pipeline_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
