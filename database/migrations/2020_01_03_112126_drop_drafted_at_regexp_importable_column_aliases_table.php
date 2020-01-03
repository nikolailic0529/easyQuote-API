<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDraftedAtRegexpImportableColumnAliasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importable_column_aliases', function (Blueprint $table) {
            $table->dropColumn(['drafted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importable_column_aliases', function (Blueprint $table) {
            $table->timestamp('drafted_at')->nullable();
        });
    }
}
