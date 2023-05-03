<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteTemplateTemplateFieldTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_template_template_field', function (Blueprint $table) {
            $table->uuid('quote_template_id');
            $table->foreign('quote_template_id')->references('id')->on('quote_templates')->onDelete('cascade');

            $table->uuid('template_field_id');
            $table->foreign('template_field_id')->references('id')->on('template_fields')->onDelete('cascade');

            $table->primary(['quote_template_id', 'template_field_id'], 'quote_template_template_field_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quote_template_template_field');
    }
}
