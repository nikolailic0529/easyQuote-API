<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QuoteNotesAssociateToQuoteVersion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_notes', function (Blueprint $table) {
            $table->uuid('quote_version_id')->nullable(true)->after('quote_id');
            $table->foreign('quote_version_id')->references('id')->on('quote_versions')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('quote_notes', function (Blueprint $table) {
            $table->dropForeign(['quote_version_id']);
            $table->dropColumn('quote_version_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
