<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveGeneratedColumnUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['activated_at']);
            $table->boolean('is_active')->after('activated_at')->virtualAs(\Illuminate\Support\Facades\DB::raw('activated_at IS NOT NULL'));

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('activated_at');

            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
}
