<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('Event name');
            $table->timestamp('occurred_at')->comment('Event occurrence timestamp');
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index([DB::raw('occurred_at desc')], 'app_events_occurred_at_index');
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

        Schema::dropIfExists('app_events');

        Schema::enableForeignKeyConstraints();
    }
};
