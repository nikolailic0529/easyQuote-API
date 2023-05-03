<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountManagerIdOpportunityTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opportunity_totals', function (Blueprint $table) {
            $table->foreignUuid('account_manager_id')->nullable()->after('user_id')->comment('Foreign key on users table')->constrained('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('opportunity_totals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_manager_id');
        });
    }
}
