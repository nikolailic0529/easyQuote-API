<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorldwideQuoteNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worldwide_quote_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('worldwide_quote_id')->comment('Foreign key on worldwide_quotes table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('user_id')->comment('Foreign key on users table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->text('text')->comment('Quote Note Text');

            $table->timestamps();
            $table->softDeletes()->index();
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

        Schema::dropIfExists('worldwide_quote_notes');

        Schema::enableForeignKeyConstraints();
    }
}
