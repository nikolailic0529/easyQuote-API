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
        Schema::table('imported_addresses', function (Blueprint $table) {
            $table->string('state_code')->nullable()->after('state')->comment('State Code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imported_addresses', function (Blueprint $table) {
            $table->dropColumn('state_code');
        });
    }
};
