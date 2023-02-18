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
        Schema::create('pipeliner_model_update_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('model_type')->index()->comment('Model type');

            $table->timestamp('latest_model_updated_at')->comment('Timestamp of the latest updated model in Pipeliner');

            $table->timestamps();
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

        Schema::dropIfExists('pipeliner_model_update_logs');

        Schema::enableForeignKeyConstraints();
    }
};
