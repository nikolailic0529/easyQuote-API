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
        Schema::table('pipeliner_sync_errors', function (Blueprint $table) {
            $table->string('error_message_hash')
                ->storedAs(DB::raw('(sha1(error_message))'))
                ->after('error_message')
                ->comment('Error message hash');
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
            $table->dropColumn(['error_message_hash']);
        });
    }
};
