<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('model_has_sharing_users', function (Blueprint $table) {
            $table->foreignUuid('user_id')
                ->comment('Foreign key to users table')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('model_type');
            $table->uuid('model_id');

            $table->index(['model_id', 'model_type']);

            $table->primary(['user_id', 'model_id', 'model_type']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('model_has_sharing_users');

        Schema::enableForeignKeyConstraints();
    }
};
