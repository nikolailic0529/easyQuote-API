<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_contacts', function (Blueprint $table) {
            $table->string('phone_2')->nullable()->after('phone')->comment('Secondary Phone Number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imported_contacts', function (Blueprint $table) {
            $table->dropColumn('phone_2');
        });
    }
};
