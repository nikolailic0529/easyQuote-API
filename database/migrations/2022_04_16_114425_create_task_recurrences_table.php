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
        Schema::create('task_recurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('task_id')->comment('Foreign key to tasks table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('user_id')->nullable()->comment('Foreign key to users table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('type_id')->comment('Foreign key to recurrence_types table')->constrained('recurrence_types')->restrictOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('date_day_id')->comment('Foreign key to date_days table')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('date_week_id')->comment('Foreign key to date_weeks table')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('date_month_id')->comment('Foreign key to date_months table')->constrained()->restrictOnDelete()->cascadeOnUpdate();

            $table->unsignedTinyInteger('day_of_week')->default(1 << 1)->comment('The days of week in bit mask');

            $table->unsignedTinyInteger('occur_every')->default(1)->comment('The number of units of a given recurrence type between occurrences');

            $table->mediumInteger('occurrences_count')->default(-1)->comment('The number of remaining occurrences');

            $table->dateTime('start_date')->comment('The effective start date of recurrence');
            $table->dateTime('end_date')->comment('The end date of recurrence');

            $table->timestamps();
            $table->softDeletes()->index();
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

        Schema::dropIfExists('task_recurrences');

        Schema::enableForeignKeyConstraints();
    }
};
