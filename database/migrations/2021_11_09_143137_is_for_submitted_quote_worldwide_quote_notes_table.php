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
        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->boolean('is_for_submitted_quote')->default(0)->after('user_id')->comment('Whether the note was created on quote submission');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->dropColumn('is_for_submitted_quote');
        });
    }
};
