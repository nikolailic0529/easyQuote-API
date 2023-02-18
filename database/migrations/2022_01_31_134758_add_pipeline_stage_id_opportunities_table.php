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
        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreignUuid('pipeline_stage_id')->nullable()->after('pipeline_id')->comment('Foreign key on pipeline_stages table')->constrained()->nullOnDelete()->cascadeOnUpdate();
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

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pipeline_stage_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
