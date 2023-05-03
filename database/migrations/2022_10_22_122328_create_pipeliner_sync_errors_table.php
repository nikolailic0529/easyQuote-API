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
        Schema::create('pipeliner_sync_errors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuidMorphs('entity');

            $table->text('error_message')->comment('Error message text');

            $table->timestamps();
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes()->index();

            $table->index(['deleted_at', 'archived_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void+
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('pipeliner_sync_errors');

        Schema::enableForeignKeyConstraints();
    }
};
