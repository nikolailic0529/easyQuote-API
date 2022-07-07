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
        Schema::create('pipeliner_custom_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('entity_name')->comment('Related entity name for field');
            $table->string('name')->comment('User friendly name for field');
            $table->string('api_name')->comment('Immutable and generated name. This name is used for integrations.');
            $table->string('eq_reference')->unique()->comment('EQ reference to field');

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

        Schema::dropIfExists('pipeliner_custom_fields');

        Schema::enableForeignKeyConstraints();
    }
};
