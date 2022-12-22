<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_allocation_records', function (Blueprint $table) {
            $table->string('result')->default('Unprocessed')->after('is_selected')
                ->comment('Record allocation result');
            $table->string('result_reason', 500)->nullable()->after('result')
                ->comment('Record allocation result reason');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_allocation_records', function (Blueprint $table) {
            //
        });
    }
};
