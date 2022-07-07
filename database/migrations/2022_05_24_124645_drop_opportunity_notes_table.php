<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::drop('opportunity_notes');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('opportunity_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->nullable()->comment('Foreign key to users table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('opportunity_id')->comment('Foreign key to opportunities table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->text('text')->comment('Content of note');

            $table->timestamps();
            $table->softDeletes()->index();
        });
    }
};
