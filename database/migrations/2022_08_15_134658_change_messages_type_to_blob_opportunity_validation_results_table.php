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
        Schema::table('opportunity_validation_results', function (Blueprint $table) {
            $table->binary('messages')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('opportunity_validation_results')->delete();

        Schema::table('opportunity_validation_results', function (Blueprint $table) {
            $table->json('messages')->change();
        });
    }
};
