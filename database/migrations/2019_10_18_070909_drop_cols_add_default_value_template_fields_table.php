<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropColsAddDefaultValueTemplateFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->dropColumn('cols');
            $table->text('default_value')->nullable();
            $table->string('name', 100)->change();
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
            $table->tinyInteger('cols')->default(12);
            $table->dropColumn('default_value');
            $table->string('name', 20)->change();
        });
    }
}
