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
        Schema::create('pipeliner_model_scroll_cursors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('model_type')->index()->comment('Model type');

            $table->foreignUuid('pipeline_id')->comment('Foreign key to pipelines table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('cursor', 1000)->comment('Base64 encoded cursor');

            $table->timestamps();

            $table->index(['created_at']);
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

        Schema::dropIfExists('pipeliner_model_scroll_cursors');

        Schema::enableForeignKeyConstraints();
    }
};
