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
        Schema::create('pipeliner_snapshot_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('snapshot_id')->comment('Foreign key to pipeliner_snapshots table')
                ->constrained('pipeliner_snapshots')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->uuid('reference');
            $table->string('type');
            $table->text('data');

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

        Schema::dropIfExists('pipeliner_snapshot_entries');

        Schema::enableForeignKeyConstraints();
    }
};
