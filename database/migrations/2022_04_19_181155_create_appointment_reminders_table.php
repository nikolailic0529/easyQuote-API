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
        Schema::create('appointment_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('appointment_id')->comment('Foreign key to appointments table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->mediumInteger('start_date_offset')->comment('Offset in seconds of appointment start date');
            $table->dateTime('snooze_date')->nullable()->comment('Snooze datetime of reminder');
            $table->tinyInteger('status')->default(0)->comment('Status of reminder');

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

        Schema::dropIfExists('appointment_reminders');

        Schema::enableForeignKeyConstraints();
    }
};
