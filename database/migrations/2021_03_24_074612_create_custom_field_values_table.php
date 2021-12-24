<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomFieldValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('custom_field_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('field_value', 250)->comment('Custom Field Value');

            $table->bigInteger('entity_order')->default(0)->comment('Entity Order');

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

        Schema::dropIfExists('custom_field_values');

        Schema::enableForeignKeyConstraints();
    }
}
