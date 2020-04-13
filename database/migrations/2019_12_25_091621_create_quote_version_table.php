<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteVersionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_version', function (Blueprint $table) {
            $table->uuid('quote_id');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');

            $table->uuid('version_id');
            $table->foreign('version_id')->references('id')->on('quotes')->onDelete('cascade');

            $table->boolean('is_using')->default(false);

            $table->primary(['quote_id', 'version_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quote_version');
    }
}
