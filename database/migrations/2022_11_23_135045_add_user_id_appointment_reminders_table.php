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
        Schema::table('appointment_reminders', function (Blueprint $table) {
            $table->foreignUuid('user_id')
                ->after('id')
                ->nullable()
                ->comment('Foreign key to users table')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
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

        Schema::table('appointment_reminders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
