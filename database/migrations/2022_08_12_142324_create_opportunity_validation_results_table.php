<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
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
        Schema::create('opportunity_validation_results', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('opportunity_id')->comment('Foreign key to opportunities table')
                ->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->json('messages')->default(new Expression("(JSON_OBJECT())"))->comment('Json array of validation messages');
            $table->boolean('is_passed')->default(0)->comment('Whether the validation is passed');

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

        Schema::dropIfExists('opportunity_validation_results');

        Schema::enableForeignKeyConstraints();
    }
};
