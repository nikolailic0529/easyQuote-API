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
        Schema::create('user_assigned_to_model', function (Blueprint $table) {
            $table->foreignUuid('user_id')->comment('Foreign key to users table')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('model_type');
            $table->uuid('model_id');

            $table->index(['model_id', 'model_type']);
            $table->primary(['user_id', 'model_id', 'model_type'], 'user_assigned_to_model_primary');

            $table->timestamps();
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

        Schema::dropIfExists('user_assigned_to_model');

        Schema::enableForeignKeyConstraints();
    }
};
