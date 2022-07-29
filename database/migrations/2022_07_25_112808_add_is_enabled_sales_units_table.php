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
        Schema::table('sales_units', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(0)
                ->after('is_default')
                ->comment('Whether the entity is enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_units', function (Blueprint $table) {
            $table->dropColumn('is_enabled');
        });
    }
};
