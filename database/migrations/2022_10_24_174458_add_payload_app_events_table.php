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
        Schema::table('app_events', function (Blueprint $table) {
            $table->json('payload')->default(DB::raw('(json_object())'))
                ->invisible()
                ->after('name')
                ->comment('Event payload');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_events', function (Blueprint $table) {
            $table->dropColumn('payload');
        });
    }
};
