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
        Schema::table('imported_companies', function (Blueprint $table) {
            $table->string('hw_country_code')->nullable()->after('country_name')->comment('Hardware Country Code');
            $table->string('sw_country_code')->nullable()->after('hw_country_code')->comment('Software Country Code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imported_companies', function (Blueprint $table) {
            $table->dropColumn([
                'hw_country_code',
                'sw_country_code',
            ]);
        });
    }
};
