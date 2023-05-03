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
        Schema::table('opportunities', function (Blueprint $table) {
            $table->unsignedSmallInteger('contract_duration_months')->nullable()->after('opportunity_closing_date')->comment('Number of Contract Duration Months');
            $table->boolean('is_contract_duration_checked')->default(0)->after('contract_duration_months')->comment('Whether The Contract Duration Is Checked');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'contract_duration_months',
                'is_contract_duration_checked',
            ]);
        });
    }
};
