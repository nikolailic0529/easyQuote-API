<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropTimestampsUnknownHeaderImportedColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_columns', function (Blueprint $table) {
            $table->dropTimestamps();
            $table->dropColumn(['unknown_header', 'drafted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imported_columns', function (Blueprint $table) {
            $table->timestamps();
            $table->timestamp('drafted_at')->nullable();
            $table->string('unknown_header')->nullable();
        });
    }
}
