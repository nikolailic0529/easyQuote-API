<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsEditableColumnDistributionFieldColumnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distribution_field_column', function (Blueprint $table) {
            $table->boolean('is_editable')->default(false)->after('is_preview_visible')->comment('Whether the field is editable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('distribution_field_column', function (Blueprint $table) {
            $table->dropColumn('is_editable');
        });
    }
}
