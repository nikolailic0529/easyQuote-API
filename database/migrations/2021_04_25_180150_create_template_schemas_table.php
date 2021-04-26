<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateSchemasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_schemas', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->json('form_data')->comment('Template Form Data');
            $table->json('data_headers')->comment('Template Data Headers');

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

        Schema::dropIfExists('template_schemas');

        Schema::enableForeignKeyConstraints();
    }
}
