<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('pipeliner_model_scroll_cursors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pipeline_id');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pipeliner_model_scroll_cursors', function (Blueprint $table) {
            $table->foreignUuid('pipeline_id')->comment('Foreign key to pipelines table')->after('model_type')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
