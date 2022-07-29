<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_units', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('pl_reference')->nullable()->comment('Reference to entity in Pipeliner');
            $table->string('unit_name');
            $table->unsignedBigInteger('entity_order')->default(0)->comment('Entity order');

            $table->timestamps();
            $table->softDeletes()->index();

            $table->unique(['unit_name', DB::raw('(IF(deleted_at IS NULL, 1, NULL))')], 'sales_units_unit_name_unique');
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

        Schema::dropIfExists('sales_units');

        Schema::enableForeignKeyConstraints();
    }
};
