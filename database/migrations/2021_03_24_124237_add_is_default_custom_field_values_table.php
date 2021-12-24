<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultCustomFieldValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('field_value')->comment('Whether the field value is default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
}
