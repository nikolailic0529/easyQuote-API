<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsPreviewVisibleQuoteFieldColumnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_field_column', function (Blueprint $table) {
            $table->boolean('is_preview_visible')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_field_column', function (Blueprint $table) {
            $table->dropColumn('is_preview_visible');
        });
    }
}
