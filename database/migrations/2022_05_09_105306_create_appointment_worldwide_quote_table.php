<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointment_worldwide_quote', function (Blueprint $table) {
            $table->foreignUuid('appointment_id')->comment('Foreign key to appointments table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_id')->comment('Foreign key to worldwide quotes table')->constrained('worldwide_quotes')->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['appointment_id', 'quote_id']);
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

        Schema::dropIfExists('appointment_worldwide_quote');

        Schema::enableForeignKeyConstraints();
    }
};
