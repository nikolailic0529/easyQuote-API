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
        Schema::table('user_assigned_to_model', function (Blueprint $table) {
            $table->date('assignment_start_date')->nullable()->after('model_id')->comment('Assignment start date');
            $table->date('assignment_end_date')->nullable()->after('assignment_start_date')->comment('Assignment start date');

            $table->index(['assignment_start_date', 'assignment_end_date'], 'user_assigned_to_model_assignment_dates_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_assigned_to_model', function (Blueprint $table) {
            $table->dropIndex('user_assigned_to_model_assignment_dates_index');
            $table->dropColumn(['assignment_start_date', 'assignment_end_date']);
        });
    }
};
