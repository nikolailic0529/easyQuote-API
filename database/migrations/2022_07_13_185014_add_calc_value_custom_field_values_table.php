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
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->float('calc_value')->default(0)->after('field_value')->comment('Calculated value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->dropColumn('calc_value');
        });
    }
};
