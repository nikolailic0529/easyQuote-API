<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeColumnIdImportableColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importable_columns', function (Blueprint $table) {
            $table->uuid('de_header_reference')->nullable()->after('id')->comment('Document Engine header reference');

            $table->index('de_header_reference');
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
            $table->dropIndex(['de_header_reference']);

            $table->dropColumn('de_header_reference');
        });
    }
}
