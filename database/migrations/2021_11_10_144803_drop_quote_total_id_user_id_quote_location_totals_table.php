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
        Schema::table('quote_location_totals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quote_total_id');
            $table->dropConstrainedForeignId('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_location_totals', function (Blueprint $table) {
            $table->foreignUuid('quote_total_id')->after('id')->comment('Foreign key on quote_totals table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('user_id')->after('country_id')->nullable()->comment('Foreign key on users table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
