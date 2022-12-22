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
        Schema::create('data_allocation_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('file_id')->comment('Foreign key to data_allocation_files table')
                ->constrained('data_allocation_files')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('opportunity_id')->comment('Foreign key to data_allocation_files table')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('user_id')->nullable()->comment('Foreign key to users table')
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedBigInteger('entity_order')->default(0)->comment('Entity order');
            $table->boolean('is_selected')->default(0)->comment('Whether the entity is selected');

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

        Schema::dropIfExists('data_allocation_records');

        Schema::enableForeignKeyConstraints();
    }
};
