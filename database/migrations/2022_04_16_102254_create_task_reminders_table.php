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
        Schema::create('task_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('task_id')->comment('Foreign key to tasks table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('user_id')->nullable()->comment('Foreign key to users table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->dateTime('set_date')->comment('Datetime of reminder');
            $table->unsignedTinyInteger('status')->default(0)->comment('Status of reminder');

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

        Schema::dropIfExists('task_reminders');

        Schema::enableForeignKeyConstraints();
    }
};
