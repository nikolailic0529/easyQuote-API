<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultPipelinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->boolean('is_default')->after('is_system')->default(0)->comment('Whether the entity is default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
}
