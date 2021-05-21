<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSystemColumnPipelinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->boolean('is_system')->default(0)->after('opportunity_form_schema_id')->comment('Whether the entity is system defined');
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
            $table->dropColumn('is_system');
        });
    }
}
