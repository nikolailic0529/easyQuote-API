<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropRegexpImportableColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importable_columns', function (Blueprint $table) {
            $table->dropColumn('regexp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importable_columns', function (Blueprint $table) {
            $table->string('regexp')->nullable();
        });
    }
}
