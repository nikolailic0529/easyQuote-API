<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTemplateFieldTypeIdTemplateFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->uuid('template_field_type_id');
            $table->foreign('template_field_type_id')->references('id')->on('template_field_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->dropForeign(['template_field_type_id']);
        });
    }
}
