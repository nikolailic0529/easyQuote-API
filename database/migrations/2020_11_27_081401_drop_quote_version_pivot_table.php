<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropQuoteVersionPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('quote_version');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('quote_version', function (Blueprint $table) {
            $table->foreignUuid('quote_id')->constrained('quotes', 'id')->comment('Foreign key on quotes table');
            $table->foreignUuid('version_id')->constrained('quotes', 'id')->comment('Foreign key on quotes table');

            $table->boolean('is_using')->default(false);

            $table->primary(['quote_id', 'version_id']);
        });
    }
}
