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
        DB::table('pipeliner_sync_errors')->delete();

        Schema::table('pipeliner_sync_errors', function (Blueprint $table) {
            $table->string('strategy_name')
                ->after('entity_id')
                ->comment('Strategy caused the error');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pipeliner_sync_errors', function (Blueprint $table) {
            $table->dropColumn('strategy_name');
        });
    }
};
