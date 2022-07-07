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
        Schema::table('imported_addresses', function (Blueprint $table) {
            $table->uuid('pl_reference')->nullable()->after('id')->comment('Reference to Contact in Pipeliner');
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
            $table->dropColumn('pl_reference');
        });
    }
};
