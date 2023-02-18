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
        Schema::create('model_has_notes', function (Blueprint $table) {
            $table->foreignUuid('note_id')->comment('Foreign key to notes table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('model_type');
            $table->uuid('model_id');

            $table->index(['model_id', 'model_type']);

            $table->primary(['note_id', 'model_id', 'model_type'], 'model_has_notes_primary');
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

        Schema::dropIfExists('model_has_notes');

        Schema::enableForeignKeyConstraints();
    }
};
