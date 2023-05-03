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
        Schema::create('model_has_tasks', function (Blueprint $table) {
            $table->foreignUuid('task_id')->comment('Foreign key to tasks table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('model_type');
            $table->uuid('model_id');

            $table->index(['model_id', 'model_type']);

            $table->primary(['task_id', 'model_id', 'model_type']);
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

        Schema::dropIfExists('model_has_tasks');

        Schema::enableForeignKeyConstraints();
    }
};
