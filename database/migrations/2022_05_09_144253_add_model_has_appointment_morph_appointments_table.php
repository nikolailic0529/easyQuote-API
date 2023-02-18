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
        Schema::table('appointments', function (Blueprint $table) {
            $table->uuid('model_has_appointment_type')->after('id');
            $table->uuid('model_has_appointment_id')->after('model_has_appointment_type');

            $table->index(['model_has_appointment_id', 'model_has_appointment_type'], 'appointments_model_has_appointment_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_model_has_appointment_index');

            $table->dropColumn([
                'model_has_appointment_type',
                'model_has_appointment_id',
            ]);
        });
    }
};
