<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropIsExpiredCreatedAtEmailAddSoftdeletesPasswordResetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('password_resets', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn(['is_expired', 'created_at', 'email']);
        });

        Schema::table('password_resets', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('password_resets', function (Blueprint $table) {
            $table->dropTimestamps();
            $table->dropSoftDeletes();
        });

        Schema::table('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->timestamp('created_at')->nullable();
            $table->boolean('is_expired')->default(false);
        });
    }
}
