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
        Schema::table('mapped_rows', function (Blueprint $table) {
            $table->boolean('is_serial_number_generated')->default(0)->after('is_selected')->comment('Whether the serial number was auto-generated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mapped_rows', function (Blueprint $table) {
            $table->dropColumn('is_serial_number_generated');
        });
    }
};
