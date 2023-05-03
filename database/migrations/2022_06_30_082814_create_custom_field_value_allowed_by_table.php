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
        Schema::create('custom_field_value_allowed_by', function (Blueprint $table) {
            $table->foreignUuid('field_value_id')->comment('Foreign key to custom_field_values table')->constrained('custom_field_values')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('allowed_by_id')->comment('Foreign key to custom_field_values table')->constrained('custom_field_values')->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['field_value_id', 'allowed_by_id'], 'custom_field_value_allowed_by_primary');
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

        Schema::dropIfExists('custom_field_value_allowed_by');

        Schema::enableForeignKeyConstraints();
    }
};
