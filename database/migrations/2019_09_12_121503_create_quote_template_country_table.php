<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuoteTemplateCountryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_template_country', function (Blueprint $table) {
            $table->uuid('quote_template_id');
            $table->foreign('quote_template_id')->references('id')->on('quote_templates');
            $table->uuid('country_id');
            $table->foreign('country_id')->references('id')->on('countries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quote_template_country');
    }
}
