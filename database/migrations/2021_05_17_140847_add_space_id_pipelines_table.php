<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSpaceIdPipelinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->uuid('space_id')->nullable()->after('id')->comment('Foreign key on spaces table');
        });

        DB::transaction(function () {
            DB::table('pipelines')
                ->update([
                    'space_id' => SP_EPD,
                ]);
        });

        Schema::table('pipelines', function (Blueprint $table) {
            $table->uuid('space_id')->nullable(false)->change();

            $table->foreign('space_id')->references('id')->on('spaces')->cascadeOnDelete()->cascadeOnUpdate();
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

        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('space_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
