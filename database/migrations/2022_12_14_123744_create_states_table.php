<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('states', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('country_id')->comment('Foreign key to countries table')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnUpdate();

            $table->string('name')->comment('State name');
            $table->string('state_code')->comment('State code');

            $table->unique(['state_code', 'country_id']);

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

        Schema::dropIfExists('states');

        Schema::enableForeignKeyConstraints();
    }
};
