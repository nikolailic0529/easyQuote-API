<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintsCustomFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_fields', function (Blueprint $table) {

            $table->boolean('is_not_deleted')->after('deleted_at')->virtualAs("IF(deleted_at IS NULL, 1, NULL)");

            $table->unique(['field_name', 'is_not_deleted']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_fields', function (Blueprint $table) {

            $table->dropUnique(['field_name', 'is_not_deleted']);

            $table->dropColumn('is_not_deleted');

        });
    }
}
