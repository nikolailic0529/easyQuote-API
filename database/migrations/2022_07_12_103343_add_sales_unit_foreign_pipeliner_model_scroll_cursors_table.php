<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $chunkSize = 1000;
        $count = DB::table('pipeliner_model_update_logs')->count();

        DB::transaction(static function () use ($chunkSize, $count) {
            for (; $count > 0; $count -= $chunkSize) {
                DB::table('pipeliner_model_update_logs')->orderBy('id')->limit($chunkSize)->delete();
            }
        });

        Schema::table('pipeliner_model_update_logs', function (Blueprint $table) {
            $table->foreignUuid('sales_unit_id')
                ->after('pipeline_id')
                ->comment('Foreign key to sales_units table')
                ->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pipeliner_model_update_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_unit_id');
        });
    }
};
