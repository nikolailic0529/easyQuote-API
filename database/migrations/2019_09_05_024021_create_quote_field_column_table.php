<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuoteFieldColumnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_field_column', function (Blueprint $table) {
            $table->uuid('quote_id');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('no action');
            $table->uuid('template_field_id');
            $table->foreign('template_field_id')->references('id')->on('template_fields')->onDelete('no action');
            $table->uuid('importable_column_id');
            $table->foreign('importable_column_id')->references('id')->on('importable_columns')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quote_field_column');
    }
}
