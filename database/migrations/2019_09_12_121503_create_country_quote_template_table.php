<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryQuoteTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_quote_template', function (Blueprint $table) {
            $table->uuid('country_id');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');

            $table->uuid('quote_template_id');
            $table->foreign('quote_template_id')->references('id')->on('quote_templates')->onDelete('cascade');

            $table->primary(['country_id', 'quote_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('country_quote_template');
    }
}
