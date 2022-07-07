<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->uuid('pl_reference')->nullable()->after('id')->comment('Reference to entity in Pipeliner');
            $table->index('pl_reference');
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
            $table->dropIndex(['pl_reference']);
            $table->dropColumn('pl_reference');
        });
    }
};
