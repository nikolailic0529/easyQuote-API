<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePipelineStagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('pipeline_id')->comment('Foreign key on pipelines table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('stage_name')->comment('Pipeline Stage name');
            $table->unsignedBigInteger('stage_order')->default(0)->comment('Pipeline Stage order');

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

        Schema::dropIfExists('pipeline_stages');

        Schema::enableForeignKeyConstraints();
    }
}
