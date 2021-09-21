<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsFromQuoteFlagQuoteNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_notes', function (Blueprint $table) {
            $table->boolean('is_from_quote')->default(0)->after('user_id')->comment('Whether the note is from quote');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_notes', function (Blueprint $table) {
            $table->dropColumn('is_from_quote');
        });
    }
}
