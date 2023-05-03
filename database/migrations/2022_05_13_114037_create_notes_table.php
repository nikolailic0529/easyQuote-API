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
        Schema::create('notes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->nullable()->comment('Foreign key to users table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->uuid('pl_reference')->nullable()->comment('Reference to Note in Pipeliner');
            $table->index('pl_reference');

            $table->text('note')->nullable()->comment('Note content');

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

        Schema::dropIfExists('notes');

        Schema::enableForeignKeyConstraints();
    }
};
