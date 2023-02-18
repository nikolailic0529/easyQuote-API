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
            $table->unsignedTinyInteger('flags')->default(0)->after('website')->comment('Imported Company Bit Flags');
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
            $table->dropColumn('flags');
        });
    }
};
